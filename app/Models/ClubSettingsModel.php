<?php

namespace App\Models;

use App\Helpers\Encryption;

class ClubSettingsModel extends BaseModel
{
    protected string $table = 'club_settings';

    /** Klucze przechowujące dane wrażliwe — auto encrypt/decrypt. */
    private const SENSITIVE_KEYS = [
        'smtp_pass_enc', 'sms_api_key', 'stripe_secret_key', 'stripe_webhook_secret',
        'federation_pzss_pass', 'federation_pzpn_pass', 'federation_pzpn_api_key',
        'federation_pzkosz_pass', 'federation_pzps_pass', 'federation_pzla_pass',
        'federation_pzhl_pass', 'federation_pzpr_pass', 'federation_pzt_pass',
        'federation_pzp_pass', 'federation_pzw_pass', 'federation_pzj_pass',
        'federation_pzkarate_pass',
    ];

    private function isSensitive(string $key): bool
    {
        if (in_array($key, self::SENSITIVE_KEYS, true)) return true;
        // Dynamiczne: dowolny klucz z "_pass" lub "_secret" lub "_api_key"
        return str_contains($key, '_pass') || str_contains($key, '_secret') || str_contains($key, '_api_key');
    }

    public function get(int $clubId, string $key, mixed $default = null): mixed
    {
        $stmt = $this->db->prepare(
            "SELECT value FROM `club_settings` WHERE club_id = ? AND `key` = ?"
        );
        $stmt->execute([$clubId, $key]);
        $val = $stmt->fetchColumn();
        if ($val === false) return $default;

        // Auto-decrypt sensitive values
        if ($this->isSensitive($key) && Encryption::isConfigured() && $val !== '') {
            $decrypted = Encryption::decrypt($val);
            return $decrypted ?? $val; // fallback jeśli nie zaszyfrowane jeszcze
        }
        return $val;
    }

    public function set(int $clubId, string $key, mixed $value, string $type = 'text', string $label = ''): void
    {
        $storeValue = (string)$value;

        // Auto-encrypt sensitive values
        if ($this->isSensitive($key) && Encryption::isConfigured() && $storeValue !== '') {
            $storeValue = Encryption::encrypt($storeValue);
        }

        $sql = "INSERT INTO `club_settings` (club_id, `key`, value, `type`, label)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value), `type` = VALUES(`type`), label = VALUES(label)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $key, $storeValue, $type, $label]);
    }

    public function getAll(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `club_settings` WHERE club_id = ? ORDER BY `key`"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
