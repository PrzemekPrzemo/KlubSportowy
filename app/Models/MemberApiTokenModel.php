<?php

namespace App\Models;

/**
 * Per-zawodnik tokeny do REST API (mobile app).
 *
 * Token surowy nie jest nigdy zapisywany — przechowujemy SHA-256 hex (nie bcrypt:
 * tokeny sa pseudolosowe high-entropy, wiec szybki hash wystarcza i pozwala na
 * lookup po tokenie bez skanowania calej tabeli).
 */
class MemberApiTokenModel extends BaseModel
{
    protected string $table = 'member_api_tokens';

    public const TOKEN_PREFIX        = 'mt_';
    public const REFRESH_PREFIX      = 'mr_';
    public const TOKEN_TTL_SECONDS   = 30 * 24 * 3600;
    public const REFRESH_TTL_SECONDS = 90 * 24 * 3600;

    public function issue(
        int $memberId,
        int $clubId,
        ?int $identityId = null,
        ?int $deviceTokenId = null,
        ?string $userAgent = null,
        ?string $ipAddress = null
    ): array {
        $rawToken   = self::TOKEN_PREFIX   . bin2hex(random_bytes(24));
        $rawRefresh = self::REFRESH_PREFIX . bin2hex(random_bytes(24));
        $expiresAt        = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS);
        $refreshExpiresAt = date('Y-m-d H:i:s', time() + self::REFRESH_TTL_SECONDS);

        $this->insert([
            'member_id'          => $memberId,
            'identity_id'        => $identityId,
            'club_id'            => $clubId,
            'token_hash'         => hash('sha256', $rawToken),
            'refresh_token_hash' => hash('sha256', $rawRefresh),
            'device_token_id'    => $deviceTokenId,
            'expires_at'         => $expiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
            'user_agent'         => $userAgent !== null ? substr($userAgent, 0, 255) : null,
            'ip_address'         => $ipAddress,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        return [
            'token'              => $rawToken,
            'refresh_token'      => $rawRefresh,
            'expires_at'         => $expiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
        ];
    }

    public function authenticate(string $rawToken): ?array
    {
        if (!str_starts_with($rawToken, self::TOKEN_PREFIX)) {
            return null;
        }
        $hash = hash('sha256', $rawToken);
        $stmt = $this->db->prepare(
            "SELECT t.*, c.is_active AS club_active, m.status AS member_status
             FROM member_api_tokens t
             JOIN clubs   c ON c.id = t.club_id
             JOIN members m ON m.id = t.member_id
             WHERE t.token_hash = ?
               AND t.revoked_at IS NULL
               AND t.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if ((int)$row['club_active'] !== 1 || $row['member_status'] !== 'aktywny') {
            return null;
        }

        $this->db->prepare("UPDATE member_api_tokens SET last_used_at = NOW() WHERE id = ?")
                 ->execute([$row['id']]);

        return $row;
    }

    public function refresh(string $rawRefreshToken): ?array
    {
        if (!str_starts_with($rawRefreshToken, self::REFRESH_PREFIX)) {
            return null;
        }
        $hash = hash('sha256', $rawRefreshToken);
        $stmt = $this->db->prepare(
            "SELECT * FROM member_api_tokens
             WHERE refresh_token_hash = ?
               AND revoked_at IS NULL
               AND refresh_expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $this->db->prepare("UPDATE member_api_tokens SET revoked_at = NOW() WHERE id = ?")
                 ->execute([$row['id']]);

        return $this->issue(
            (int)$row['member_id'],
            (int)$row['club_id'],
            $row['identity_id'] !== null ? (int)$row['identity_id'] : null,
            $row['device_token_id'] !== null ? (int)$row['device_token_id'] : null,
            $row['user_agent'] ?? null,
            $row['ip_address'] ?? null
        );
    }

    public function revoke(string $rawToken): bool
    {
        $hash = hash('sha256', $rawToken);
        $stmt = $this->db->prepare(
            "UPDATE member_api_tokens SET revoked_at = NOW()
             WHERE token_hash = ? AND revoked_at IS NULL"
        );
        $stmt->execute([$hash]);
        return $stmt->rowCount() > 0;
    }

    public function revokeAllForMember(int $memberId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE member_api_tokens SET revoked_at = NOW()
             WHERE member_id = ? AND revoked_at IS NULL"
        );
        $stmt->execute([$memberId]);
        return $stmt->rowCount();
    }

    public function attachDeviceToken(int $tokenId, int $deviceTokenId): void
    {
        $this->db->prepare("UPDATE member_api_tokens SET device_token_id = ? WHERE id = ?")
                 ->execute([$deviceTokenId, $tokenId]);
    }
}
