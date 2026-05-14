<?php

namespace App\Helpers;

use App\Models\ClubSettingsModel;
use App\Models\SmsQueueModel;
use App\Models\SettingModel;

/**
 * Wysyłka SMS — pluggable backend (SMSAPI.pl lub Twilio).
 *
 * Konfiguracja przez club_settings lub globalne settings:
 *   sms_provider      = 'smsapi' | 'twilio' | 'log'
 *   sms_api_key       = klucz API
 *   sms_from          = nadawca (alpha-numeric dla SMSAPI, numer dla Twilio)
 *   sms_twilio_sid    = tylko Twilio
 *
 * Domyślnie provider = 'log' (nie wysyła, tylko loguje — przydatne w dev).
 */
class SmsService
{
    public static function send(int $clubId, string $toPhone, string $message): bool
    {
        $config = self::resolveConfig($clubId);
        $phone  = self::normalizePhone($toPhone);
        $message = mb_substr($message, 0, 500);

        return match ($config['provider']) {
            'smsapi' => self::sendViaSmsApi($config, $phone, $message),
            'twilio' => self::sendViaTwilio($config, $phone, $message),
            default  => self::sendViaLog($phone, $message),
        };
    }

    public static function queue(int $clubId, string $toPhone, string $message, ?string $toName = null): int
    {
        return (new SmsQueueModel())->enqueue($clubId, $toPhone, $toName, $message, Auth::id());
    }

    public static function processQueue(int $batchSize = 30): int
    {
        $q = new SmsQueueModel();
        $pending = $q->pending($batchSize);
        $sent = 0;
        foreach ($pending as $row) {
            $q->markSending((int)$row['id']);
            try {
                $ok = self::send((int)$row['club_id'], $row['to_phone'], $row['message']);
                if ($ok) {
                    $q->markSent((int)$row['id']);
                    $sent++;
                } else {
                    $q->markFailed((int)$row['id'], 'provider returned false');
                }
            } catch (\Throwable $e) {
                $q->markFailed((int)$row['id'], $e->getMessage());
            }
        }
        return $sent;
    }

    private static function resolveConfig(int $clubId): array
    {
        $cs = new ClubSettingsModel();
        $gs = new SettingModel();

        // Domyslny "from" — globalny / per-klub setting.
        $defaultFrom = $cs->get($clubId, 'sms_from', $gs->get('sms_from', 'KlubSport'));

        // Whitelabel: sms_sender_id z club_customization nadpisuje sms_from.
        try {
            $whitelabel = \App\Helpers\ClubBranding::forClub($clubId);
            $defaultFrom = $whitelabel->smsSenderOrDefault((string)$defaultFrom);
        } catch (\Throwable) {
            // ClubCustomizationModel niegotowy lub brak DB — degrade gracefully.
        }

        return [
            'provider' => $cs->get($clubId, 'sms_provider', $gs->get('sms_provider', 'log')),
            'api_key'  => $cs->get($clubId, 'sms_api_key',  $gs->get('sms_api_key',  '')),
            'from'     => $defaultFrom,
            'sid'      => $cs->get($clubId, 'sms_twilio_sid', $gs->get('sms_twilio_sid', '')),
        ];
    }

    private static function normalizePhone(string $phone): string
    {
        // Zachowaj cyfry i plus. Dla PL dodaj +48 jeśli brak prefixu.
        $p = preg_replace('/[^\d+]/', '', $phone);
        if ($p === '') return $p;
        if (!str_starts_with($p, '+')) {
            if (strlen($p) === 9) {
                $p = '+48' . $p;
            }
        }
        return $p;
    }

    private static function sendViaSmsApi(array $config, string $phone, string $message): bool
    {
        if (empty($config['api_key'])) return false;
        $ch = curl_init('https://api.smsapi.pl/sms.do');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $config['api_key']],
            CURLOPT_POSTFIELDS     => http_build_query([
                'to'     => $phone,
                'from'   => $config['from'],
                'message'=> $message,
                'format' => 'json',
                'encoding' => 'utf-8',
            ]),
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200 && $resp !== false;
    }

    private static function sendViaTwilio(array $config, string $phone, string $message): bool
    {
        if (empty($config['sid']) || empty($config['api_key'])) return false;
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$config['sid']}/Messages.json";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $config['sid'] . ':' . $config['api_key'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'To'   => $phone,
                'From' => $config['from'],
                'Body' => $message,
            ]),
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private static function sendViaLog(string $phone, string $message): bool
    {
        $log = ROOT_PATH . '/storage/logs/sms.log';
        @file_put_contents(
            $log,
            "[" . date('Y-m-d H:i:s') . "] SMS -> {$phone}: {$message}\n",
            FILE_APPEND
        );
        return true;
    }
}
