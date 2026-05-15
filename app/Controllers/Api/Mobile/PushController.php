<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;

/**
 * Mobile API v1 — FCM/APNS device-token registration.
 * Stores tokens in `device_tokens` (existing schema, extended in migration 071
 * with `app_version` and `device_model`).
 */
class PushController extends V1Controller
{
    /**
     * POST /api/mobile/v1/push/register
     * Body: { token, platform: ios|android, app_version?, device_model? }
     */
    public function register(): void
    {
        $this->requireAuth();
        $input = $this->input();
        $token       = trim((string)($input['token'] ?? ''));
        $platform    = strtolower(trim((string)($input['platform'] ?? '')));
        $appVersion  = trim((string)($input['app_version'] ?? '')) ?: null;
        $deviceModel = trim((string)($input['device_model'] ?? '')) ?: null;

        if ($token === '') {
            $this->error('token jest wymagany.', 422, 'validation', ['token' => 'required']);
        }
        if (!in_array($platform, ['ios', 'android', 'web'], true)) {
            $this->error('Nieprawidłowa platforma (ios|android|web).', 422, 'validation', ['platform' => 'invalid']);
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO device_tokens (member_id, token, platform, app_version, device_model, is_active)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                member_id = VALUES(member_id),
                platform  = VALUES(platform),
                app_version = VALUES(app_version),
                device_model = VALUES(device_model),
                is_active = 1,
                updated_at = NOW()"
        );
        $stmt->execute([$this->memberId, $token, $platform, $appVersion, $deviceModel]);

        $this->json(['registered' => true, 'platform' => $platform]);
    }

    /**
     * POST /api/mobile/v1/push/unregister
     * Body: { token }
     */
    public function unregister(): void
    {
        $this->requireAuth();
        $input = $this->input();
        $token = trim((string)($input['token'] ?? ''));
        if ($token === '') {
            $this->error('token jest wymagany.', 422, 'validation', ['token' => 'required']);
        }
        Database::pdo()
            ->prepare("UPDATE device_tokens SET is_active = 0, updated_at = NOW() WHERE token = ? AND member_id = ?")
            ->execute([$token, $this->memberId]);
        $this->json(['unregistered' => true]);
    }
}
