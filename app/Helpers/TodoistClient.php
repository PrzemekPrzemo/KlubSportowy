<?php

namespace App\Helpers;

/**
 * Klient REST + Sync API Todoista.
 *
 * Konfiguracja: config/todoist.php (defaults) + config/todoist.local.php (gitignored).
 *
 * Public methods:
 *  - isConfigured(): czy token + project_id sa ustawione
 *  - createTask(content, description, priority): tworzy task, zwraca task_id lub null
 *  - uploadFile(path, name): upload do /sync/v9/uploads/add, zwraca attachment array
 *  - addCommentWithAttachment(taskId, content, attachment): dodaje komentarz z plikiem
 */
class TodoistClient
{
    private const REST_URL = 'https://api.todoist.com/rest/v2';
    private const SYNC_URL = 'https://api.todoist.com/sync/v9';

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

        $resp = $this->httpJson('POST', self::REST_URL . '/tasks', $payload);
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

        $ch = curl_init(self::SYNC_URL . '/uploads/add');
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
            $this->httpJson('POST', self::REST_URL . '/comments', $payload);
            return true;
        } catch (\Throwable) {
            return false;
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
