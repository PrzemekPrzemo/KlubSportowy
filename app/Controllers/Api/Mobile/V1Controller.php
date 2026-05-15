<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\MobileApiAuth;

/**
 * Base controller for Mobile API v1.
 *
 * Responsibilities:
 *  - Force JSON content-type and CORS for the mobile app.
 *  - Unified `json()` / `error()` envelope: { ok: true, data } | { ok: false, error }.
 *  - Optional `requireAuth()` that hydrates $this->member, $this->club, $this->memberId, $this->clubId.
 *
 * Sub-controllers may call requireAuth() in actions that need a logged-in member.
 * Public endpoints (login/forgot/select-club) deliberately skip it.
 */
abstract class V1Controller
{
    protected array $member = [];
    protected ?array $club = null;
    protected int $memberId = 0;
    protected int $clubId = 0;

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
        // Permissive CORS — mobile app and admin tools call this directly over HTTPS.
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /** Populate $this->member / $this->club from a valid bearer token (or emit 401 and exit). */
    protected function requireAuth(): void
    {
        $ctx = MobileApiAuth::requireAuth();
        $this->member   = $ctx['member'];
        $this->club     = $ctx['club'];
        $this->memberId = (int)$this->member['id'];
        $this->clubId   = (int)$this->member['club_id'];
    }

    /** Send a success envelope. */
    protected function json(mixed $data, int $status = 200, array $extra = []): never
    {
        http_response_code($status);
        echo json_encode(
            array_merge(['ok' => true, 'data' => $data], $extra),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    /** Send an error envelope. */
    protected function error(string $message, int $status = 400, string $code = 'bad_request', array $fields = []): never
    {
        http_response_code($status);
        $payload = [
            'ok'    => false,
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
        ];
        if ($fields) {
            $payload['error']['fields'] = $fields;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Decode JSON body, fall back to $_POST. */
    protected function input(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return $_POST;
    }

    /** Helper: paginated envelope. */
    protected function paginated(array $pagination): never
    {
        $this->json(
            $pagination['data'] ?? [],
            200,
            ['pagination' => [
                'total'        => $pagination['total'] ?? 0,
                'per_page'     => $pagination['per_page'] ?? 20,
                'current_page' => $pagination['current_page'] ?? 1,
                'last_page'    => $pagination['last_page'] ?? 1,
            ]]
        );
    }
}
