<?php

namespace App\Controllers\Api;

use App\Helpers\ClubContext;
use App\Helpers\RateLimiter;
use App\Models\ApiKeyModel;

/**
 * Bazowy kontroler dla REST API v1.
 *
 * Uwierzytelnianie: nagłówek Authorization: Bearer ks_...
 * Rate limiting: per API key (domyślnie 60/min).
 * Odpowiedzi: JSON.
 * Club context: ustawiany automatycznie z api_keys.club_id.
 */
abstract class BaseApiController
{
    protected array $apiKey;
    protected int $clubId;

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = $this->extractBearerToken();
        if ($token === null) {
            $this->error('Brak nagłówka Authorization: Bearer <api_key>', 401);
        }

        $model = new ApiKeyModel();
        $key   = $model->authenticate($token);
        if ($key === null) {
            $this->error('Nieprawidłowy lub nieaktywny klucz API.', 401);
        }

        // Rate limit per API key
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $action = 'api_key_' . $key['id'];
        if (!RateLimiter::check($ip, $action, (int)$key['rate_limit'], 1)) {
            $this->error('Zbyt wiele żądań. Limit: ' . (int)$key['rate_limit'] . '/min.', 429);
        }
        RateLimiter::hit($ip, $action);

        $this->apiKey = $key;
        $this->clubId = (int)$key['club_id'];
        ClubContext::set($this->clubId);
    }

    protected function requireScope(string $scope): void
    {
        if (!(new ApiKeyModel())->hasScope($this->apiKey, $scope)) {
            $this->error("Brak uprawnień do zasobu: {$scope}", 403);
        }
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function error(string $message, int $status = 400): never
    {
        http_response_code($status);
        echo json_encode(['error' => $message, 'status' => $status], JSON_UNESCAPED_UNICODE);
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
