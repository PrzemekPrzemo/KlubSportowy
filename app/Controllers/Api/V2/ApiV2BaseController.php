<?php

namespace App\Controllers\Api\V2;

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\RateLimiter;

/**
 * Bazowy controller Public API v2 (dla integracji zewnetrznych — np. CRM, ksiegowosc, BI).
 *
 * Auth: Authorization: Bearer <token>
 *   - Plain token (np. "cdk_v2_<48hex>") generowany przy create, hash SHA-256 w DB.
 *   - Tylko hash trzymany w `api_v2_tokens.token_hash`; plain pokazany RAZ.
 *
 * Scope check w kazdej akcji przez requireScope('members:read').
 * Multi-tenant: ClubContext::set(token.club_id) — wszystkie ClubScopedModel filtruja automatycznie.
 * Rate limit: 100 req/min per token (wykorzystuje istniejacy RateLimiter).
 *
 * Format odpowiedzi:
 *   - sukces: {"data": ..., "meta": {...}}
 *   - blad:   {"error": {"code": "...", "message": "..."}}
 */
abstract class ApiV2BaseController
{
    protected int $clubId = 0;
    protected int $tokenId = 0;
    /** @var array<string> */
    protected array $scopes = [];
    protected array $token = [];

    /** Rate limit: 100 req/min per token (sliding window upraszczamy do okna 1 minuty). */
    private const RATE_LIMIT_PER_MIN = 100;
    private const RATE_LIMIT_WINDOW_MIN = 1;

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
        // API v2 nie ma CSRF (token auth wystarcza); nie chcemy cookies / session.
        header('X-Content-Type-Options: nosniff');

        $raw = self::extractBearerToken();
        if ($raw === null || $raw === '') {
            $this->error('Missing Authorization: Bearer header.', 401, 'missing_token');
        }

        $hash = hash('sha256', $raw);
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id, club_id, name, scopes, expires_at, revoked_at
             FROM api_v2_tokens
             WHERE token_hash = ?
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->error('Invalid API token.', 401, 'invalid_token');
        }
        if ($row['revoked_at'] !== null) {
            $this->error('API token has been revoked.', 401, 'token_revoked');
        }
        if ($row['expires_at'] !== null && strtotime((string)$row['expires_at']) <= time()) {
            $this->error('API token has expired.', 401, 'token_expired');
        }

        $this->tokenId = (int)$row['id'];
        $this->clubId  = (int)$row['club_id'];
        $this->scopes  = json_decode((string)$row['scopes'], true) ?: [];
        $this->token   = $row;

        // Rate limit per token (zero-cost gdy IP nieznane; uzywamy "tok-<id>" jako ip-pseudonimu).
        $rlKey = 'tok-' . $this->tokenId;
        $action = 'api_v2';
        if (!RateLimiter::check($rlKey, $action, self::RATE_LIMIT_PER_MIN, self::RATE_LIMIT_WINDOW_MIN)) {
            $this->error('Rate limit exceeded (100 req/min).', 429, 'rate_limited');
        }
        RateLimiter::hit($rlKey, $action, self::RATE_LIMIT_PER_MIN, self::RATE_LIMIT_WINDOW_MIN);

        // Multi-tenant: kazdy ClubScopedModel teraz filtruje WHERE club_id = <token.club_id>
        ClubContext::set($this->clubId);

        // last_used_at — best-effort
        try {
            $db->prepare("UPDATE api_v2_tokens SET last_used_at = NOW() WHERE id = ?")
               ->execute([$this->tokenId]);
        } catch (\Throwable) {}
    }

    /** Sprawdz czy token ma wymagany scope (np. 'members:read'); w przeciwnym razie 403. */
    protected function requireScope(string $scope): void
    {
        if (!in_array($scope, $this->scopes, true) && !in_array('*', $this->scopes, true)) {
            $this->error("Missing required scope: {$scope}", 403, 'insufficient_scope');
        }
    }

    /** @return never */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Pakiet odpowiedzi z `meta` paginacji.
     * @param array<int,mixed> $data
     * @return never
     */
    protected function ok(array $data, ?array $meta = null): void
    {
        $payload = ['data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }
        $this->json($payload, 200);
    }

    /** @return never */
    protected function error(string $message, int $status = 400, string $code = 'error'): void
    {
        http_response_code($status);
        echo json_encode([
            'error' => ['code' => $code, 'message' => $message],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Konwertuj wynik z BaseModel::paginate() do envelope API v2. */
    protected function paginated(array $paginationResult, int $perPage = 50): array
    {
        return [
            'data' => $paginationResult['data'] ?? [],
            'meta' => [
                'page'     => (int)($paginationResult['current_page'] ?? 1),
                'per_page' => (int)($paginationResult['per_page'] ?? $perPage),
                'total'    => (int)($paginationResult['total'] ?? 0),
                'last_page'=> (int)($paginationResult['last_page'] ?? 1),
            ],
        ];
    }

    /** Wymus paginacje page>=1, per_page in [1..100]. */
    protected function pageParams(int $defaultPerPage = 50): array
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? $defaultPerPage)));
        return [$page, $perPage];
    }

    private static function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if ($header === '' && function_exists('getallheaders')) {
            $all = getallheaders();
            foreach ((array)$all as $k => $v) {
                if (strcasecmp((string)$k, 'Authorization') === 0) {
                    $header = (string)$v;
                    break;
                }
            }
        }
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
