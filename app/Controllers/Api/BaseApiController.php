<?php

namespace App\Controllers\Api;

use App\Helpers\ClubContext;
use App\Helpers\MemberTokenAuth;
use App\Helpers\RateLimiter;
use App\Models\ApiKeyModel;

/**
 * Bazowy kontroler dla REST API v1.
 *
 * Dual auth:
 *   - Bearer ks_... → api_keys (club-level, scope-based, dla integracji)
 *   - Bearer mt_... → member_api_tokens (per-zawodnik, mobile app)
 *
 * Tokeny api_keys sa bcrypt-hashed (krotkie staticzne klucze, lookup po prefiksie).
 * Tokeny mobile sa SHA-256 hashed (wysoka entropia, lookup po pelnym hashu).
 */
abstract class BaseApiController
{
    protected array $apiKey = [];
    protected int $clubId;
    protected ?int $memberId = null;
    protected ?int $identityId = null;
    protected ?int $memberTokenId = null;
    protected array $scopes = [];

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = $this->extractBearerToken();
        if ($token === null) {
            $this->error('Brak nagłówka Authorization: Bearer <token>.', 401, 'missing_token');
        }

        if (str_starts_with($token, 'mt_')) {
            $auth = MemberTokenAuth::authenticate($token);
            if ($auth === null) {
                $this->error('Nieprawidłowy lub wygasły token zawodnika.', 401, 'invalid_member_token');
            }
            $this->clubId        = $auth['club_id'];
            $this->memberId      = $auth['member_id'];
            $this->identityId    = $auth['identity_id'];
            $this->memberTokenId = $auth['token_id'];
            $this->scopes        = ['member:*'];
            ClubContext::set($this->clubId);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $action = 'member_token_' . $this->memberTokenId;
            if (!RateLimiter::check($ip, $action, 120, 1)) {
                $this->error('Zbyt wiele żądań.', 429, 'rate_limited');
            }
            RateLimiter::hit($ip, $action);
            return;
        }

        if (str_starts_with($token, 'ks_')) {
            $model = new ApiKeyModel();
            $key   = $model->authenticate($token);
            if ($key === null) {
                $this->error('Nieprawidłowy lub nieaktywny klucz API.', 401, 'invalid_api_key');
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $action = 'api_key_' . $key['id'];
            if (!RateLimiter::check($ip, $action, (int)$key['rate_limit'], 1)) {
                $this->error('Zbyt wiele żądań. Limit: ' . (int)$key['rate_limit'] . '/min.', 429, 'rate_limited');
            }
            RateLimiter::hit($ip, $action);

            $this->apiKey = $key;
            $this->clubId = (int)$key['club_id'];
            $this->scopes = $key['scopes_array'] ?? [];
            ClubContext::set($this->clubId);
            return;
        }

        $this->error('Nieznany format tokenu.', 401, 'unknown_token_format');
    }

    protected function requireScope(string $scope): void
    {
        // Member tokens have implicit access to their own data, scope checks dont apply.
        if ($this->memberId !== null) {
            return;
        }
        if (!(new ApiKeyModel())->hasScope($this->apiKey, $scope)) {
            $this->error("Brak uprawnień do zasobu: {$scope}", 403, 'forbidden_scope');
        }
    }

    protected function requireMember(): void
    {
        if ($this->memberId === null) {
            $this->error('Endpoint dostępny tylko dla tokenów zawodnika (mt_...).', 403, 'member_token_required');
        }
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function error(string $message, int $status = 400, string $code = 'error'): never
    {
        http_response_code($status);
        echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function paginated(array $paginationResult): never
    {
        $this->json([
            'data'        => $paginationResult['data'] ?? [],
            'pagination'  => [
                'total'        => $paginationResult['total'] ?? 0,
                'per_page'     => $paginationResult['per_page'] ?? 20,
                'current_page' => $paginationResult['current_page'] ?? 1,
                'last_page'    => $paginationResult['last_page'] ?? 1,
            ],
        ]);
    }

    private function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
