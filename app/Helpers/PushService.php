<?php

namespace App\Helpers;

use App\Models\DeviceTokenModel;
use App\Models\MemberModel;
use App\Models\MemberNotificationModel;
use App\Models\SettingModel;

/**
 * Push notifications via Firebase Cloud Messaging (FCM) HTTP v1 API.
 *
 * Config: settings.fcm_project_id, settings.fcm_server_key
 * Fallback: log-only jeśli FCM nie skonfigurowany.
 */
class PushService
{
    /**
     * Wyslij push do konkretnego zawodnika ORAZ zapisz rekord w member_notifications
     * (in-app inbox dziala nawet gdy FCM zawiedzie — wieksza niezawodnosc).
     */
    public static function sendToMember(int $memberId, string $title, string $body, array $data = []): void
    {
        // Persist inbox row first — niezalezne od FCM.
        try {
            $member = (new MemberModel())->withoutScope()->findById($memberId);
            $clubId = $member !== null ? (int)$member['club_id'] : null;
            if ($clubId !== null) {
                $type = isset($data['type']) && is_string($data['type']) ? $data['type'] : 'general';
                (new MemberNotificationModel())->create(
                    $memberId,
                    $clubId,
                    $type,
                    $title,
                    $body !== '' ? $body : null,
                    !empty($data) ? $data : null
                );
            }
        } catch (\Throwable $e) {
            error_log('PushService inbox persist failed: ' . $e->getMessage());
        }

        $tokens = (new DeviceTokenModel())->tokensForMember($memberId);
        self::sendToTokens($tokens, $title, $body, !empty($data) ? $data : null);
    }

    /** Wyślij push do wszystkich zawodników klubu. */
    public static function sendToClub(int $clubId, string $title, string $body, ?array $data = null): int
    {
        $tokens = (new DeviceTokenModel())->tokensForClub($clubId);
        return self::sendToTokens($tokens, $title, $body, $data);
    }

    private static function sendToTokens(array $tokens, string $title, string $body, ?array $data): int
    {
        if (empty($tokens)) return 0;

        $config = self::getConfig();
        if (!$config['project_id'] || !$config['server_key']) {
            self::logFallback($tokens, $title, $body);
            return 0;
        }

        $sent = 0;
        foreach ($tokens as $t) {
            $payload = [
                'message' => [
                    'token' => $t['token'],
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                ],
            ];
            if ($data) {
                $payload['message']['data'] = array_map('strval', $data);
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$config['project_id']}/messages:send";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $config['server_key'],
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT    => 10,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code >= 200 && $code < 300) {
                $sent++;
            } else {
                error_log("FCM push failed for token {$t['token']}: HTTP {$code} — {$resp}");
                if ($code === 404 || $code === 410) {
                    (new DeviceTokenModel())->unregister($t['token']);
                }
            }
        }
        return $sent;
    }

    private static function getConfig(): array
    {
        $gs = new SettingModel();
        return [
            'project_id' => $gs->get('fcm_project_id', ''),
            'server_key' => $gs->get('fcm_server_key', ''),
        ];
    }

    private static function logFallback(array $tokens, string $title, string $body): void
    {
        $log = ROOT_PATH . '/storage/logs/push.log';
        $count = count($tokens);
        @file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] PUSH (log-only, FCM not configured): "
            . "{$title} → {$count} tokens: {$body}\n", FILE_APPEND);
    }
}
