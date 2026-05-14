<?php

namespace App\Helpers;

/**
 * Klient API Todoista (Unified API v1).
 *
 * Konfiguracja: config/todoist.php (defaults) + config/todoist.local.php (gitignored).
 *
 * UWAGA: Todoist zdeprecował endpointy /rest/v2 i /sync/v9 (HTTP 410 od 2025).
 * Klient używa nowych endpointów /api/v1/ — patrz https://developer.todoist.com/api/v1
 *
 * Public methods:
 *  - isConfigured(): czy token + project_id są ustawione
 *  - createTask(content, description, priority): tworzy task, zwraca task_id lub null
 *  - uploadFile(path, name): upload do /api/v1/uploads/upload, zwraca attachment array
 *  - addCommentWithAttachment(taskId, content, attachment): dodaje komentarz z plikiem
 *  - addComment(taskId, content): dodaje zwykly komentarz tekstowy
 *  - getTask(taskId): GET /tasks/{id}, zwraca array lub null gdy 404
 *  - closeTask(taskId): POST /tasks/{id}/close
 *  - reopenTask(taskId): POST /tasks/{id}/reopen
 */
class TodoistClient
{
    private const API_URL = 'https://api.todoist.com/api/v1';

    private string $token;
    private string $projectId;
    private int $timeout;

    public function __construct(?array $config = null)
    {
        $base  = file_exists(ROOT_PATH . '/config/todoist.php')
            ? require ROOT_PATH . '/config/todoist.php'
            : [];
        $local = file_exists(ROOT_PATH . '/config/todoist.local.php')
            ? require ROOT_PATH . '/config/todoist.local.php'
            : [];
        $cfg = array_merge(
            is_array($base) ? $base : [],
            is_array($local) ? $local : [],
            $config ?? []
        );

        $this->token     = (string)($cfg['api_token'] ?? '');
        $this->projectId = (string)($cfg['project_id'] ?? '');
        $this->timeout   = (int)($cfg['timeout'] ?? 10);
    }

    public function isConfigured(): bool
    {
        return $this->token !== '' && $this->projectId !== '';
    }

    /**
     * Utworz task w Todoist. Zwraca task_id lub null przy bledzie.
     * @throws \RuntimeException gdy nie skonfigurowany lub HTTP error
     */
    public function createTask(string $content, string $description = '', string $priority = 'p3'): ?string
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Todoist API token not configured. See config/todoist.local.php.example');
        }

        $priorityMap = ['p1' => 4, 'p2' => 3, 'p3' => 2, 'p4' => 1];
        $payload = [
            'content'     => mb_substr($content, 0, 500),
            'description' => $description,
            'project_id'  => $this->projectId,
            'priority'    => $priorityMap[$priority] ?? 2,
        ];

        $resp = $this->httpJson('POST', self::API_URL . '/tasks', $payload);
        $id = $resp['id'] ?? null;
        return $id !== null ? (string)$id : null;
    }

    /**
     * Upload pliku do Todoist + zwroc attachment array do uzycia w comment.
     * Zwraca null przy bledzie (nie rzuca — upload screenshota jest best-effort).
     */
    public function uploadFile(string $filePath, string $fileName): ?array
    {
        if (!$this->isConfigured() || !is_file($filePath)) return null;

        $mime = function_exists('mime_content_type')
            ? (mime_content_type($filePath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        $ch = curl_init(self::API_URL . '/uploads/upload');
        if ($ch === false) return null;

        $cfile = new \CURLFile($filePath, $mime, $fileName);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout * 3,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['file' => $cfile],
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->token],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $code < 200 || $code >= 300) return null;
        $data = json_decode((string)$body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Dodaj komentarz z attachmentem do taska.
     */
    public function addCommentWithAttachment(string $taskId, string $content, array $attachment): bool
    {
        if (!$this->isConfigured()) return false;

        $payload = [
            'task_id'    => $taskId,
            'content'    => $content,
            'attachment' => $attachment,
        ];
        try {
            $this->httpJson('POST', self::API_URL . '/comments', $payload);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Dodaj zwykly tekstowy komentarz do taska.
     */
    public function addComment(string $taskId, string $content): bool
    {
        if (!$this->isConfigured()) return false;
        $payload = [
            'task_id' => $taskId,
            'content' => mb_substr($content, 0, 15000),
        ];
        try {
            $this->httpJson('POST', self::API_URL . '/comments', $payload);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Pobierz task z Todoist po ID.
     *
     * @return array<string,mixed>|null array tasku, null gdy 404 (zostal usuniety w Todoist)
     * @throws \RuntimeException przy innym bledzie HTTP lub konfiguracji
     */
    public function getTask(string $taskId): ?array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Todoist API token not configured.');
        }

        $url = self::API_URL . '/tasks/' . rawurlencode($taskId);
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Cannot init cURL');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($code === 404) {
            return null;
        }
        if ($body === false || $err !== '') {
            throw new \RuntimeException("Todoist cURL error: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Todoist HTTP {$code}: " . substr((string)$body, 0, 300));
        }
        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Todoist invalid JSON response');
        }
        return $data;
    }

    /**
     * Zamknij task w Todoist (POST /tasks/{id}/close).
     * Zwraca true gdy 2xx (lub 204 No Content), false przy bledzie.
     */
    public function closeTask(string $taskId): bool
    {
        if (!$this->isConfigured()) return false;
        try {
            $this->httpNoContent('POST', self::API_URL . '/tasks/' . rawurlencode($taskId) . '/close');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Otworz ponownie zamkniety task (POST /tasks/{id}/reopen).
     */
    public function reopenTask(string $taskId): bool
    {
        if (!$this->isConfigured()) return false;
        try {
            $this->httpNoContent('POST', self::API_URL . '/tasks/' . rawurlencode($taskId) . '/reopen');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Request bez wymaganej response body (POST close/reopen zwracaja 204).
     */
    private function httpNoContent(string $method, string $url): void
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Cannot init cURL');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            throw new \RuntimeException("Todoist cURL error: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Todoist HTTP {$code}: " . substr((string)$body, 0, 300));
        }
    }

    /**
     * @param array<string,mixed>|null $payload
     * @return array<mixed>
     */
    private function httpJson(string $method, string $url, ?array $payload): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Cannot init cURL');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            throw new \RuntimeException("Todoist cURL error: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Todoist HTTP {$code}: " . substr((string)$body, 0, 300));
        }
        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Todoist invalid JSON response');
        }
        return $data;
    }
}
