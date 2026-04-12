<?php

namespace App\Models;

class ApiKeyModel extends ClubScopedModel
{
    protected string $table = 'api_keys';

    /**
     * Generuje nowy klucz API. Zwraca [id, raw_key] — raw_key wyświetlany
     * użytkownikowi tylko raz (potem przechowywany jako hash).
     */
    public function generate(int $clubId, string $name, array $scopes = [], int $rateLimit = 60, ?int $userId = null): array
    {
        $raw    = 'ks_' . bin2hex(random_bytes(24)); // ks_ + 48 hex = 51 chars
        $prefix = substr($raw, 0, 10);
        $hash   = password_hash($raw, PASSWORD_BCRYPT);

        $id = $this->insert([
            'club_id'    => $clubId,
            'name'       => $name,
            'key_hash'   => $hash,
            'key_prefix' => $prefix,
            'scopes'     => json_encode($scopes),
            'rate_limit' => $rateLimit,
            'created_by' => $userId,
        ]);

        return ['id' => $id, 'raw_key' => $raw];
    }

    /**
     * Uwierzytelnia klucz API. Zwraca rekord + club_id lub null.
     * Aktualizuje last_used_at.
     */
    public function authenticate(string $rawKey): ?array
    {
        $prefix = substr($rawKey, 0, 10);
        $stmt = $this->db->prepare(
            "SELECT ak.*, c.name AS club_name, c.is_active AS club_active
             FROM api_keys ak
             JOIN clubs c ON c.id = ak.club_id
             WHERE ak.key_prefix = ? AND ak.is_active = 1 AND c.is_active = 1"
        );
        $stmt->execute([$prefix]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($rawKey, $row['key_hash'])) {
            return null;
        }

        // Touch last_used_at
        $this->db->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?")
                 ->execute([$row['id']]);

        $row['scopes_array'] = json_decode($row['scopes'] ?? '[]', true) ?: [];
        return $row;
    }

    public function hasScope(array $apiKey, string $scope): bool
    {
        $scopes = $apiKey['scopes_array'] ?? [];
        // Pusta lista = pełen dostęp
        if (empty($scopes)) return true;
        return in_array($scope, $scopes, true) || in_array('*', $scopes, true);
    }
}
