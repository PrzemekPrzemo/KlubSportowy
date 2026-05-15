<?php

namespace App\Helpers;

use App\Models\MemberModel;

/**
 * MobileApiAuth — bearer-token authentication for Mobile API v1.
 *
 * - Raw tokens are produced once and returned to the client.
 *   DB stores only SHA-256 hashes (column `token_hash` / `refresh_token_hash`).
 * - Access tokens live for 30 days; refresh tokens for 90 days.
 * - `authenticate()` updates `last_used_at` for observability.
 * - `requireAuth()` short-circuits with a 401 JSON envelope when missing/invalid.
 */
class MobileApiAuth
{
    /** Access token lifetime (days). */
    public const ACCESS_TTL_DAYS = 30;

    /** Refresh token lifetime (days). */
    public const REFRESH_TTL_DAYS = 90;

    /**
     * Issue a fresh access + refresh token pair for a member/club.
     *
     * @return array{access:string, refresh:string, expires_at:string, refresh_expires_at:string, token_id:int}
     */
    public static function issueToken(
        int $memberId,
        int $clubId,
        ?string $deviceInfo = null,
        ?string $userAgent = null,
        ?string $appVersion = null,
        ?int $identityId = null
    ): array {
        $access  = bin2hex(random_bytes(32));   // 64 hex chars
        $refresh = bin2hex(random_bytes(32));

        $accessHash  = hash('sha256', $access);
        $refreshHash = hash('sha256', $refresh);

        $now              = new \DateTimeImmutable('now');
        $expiresAt        = $now->modify('+' . self::ACCESS_TTL_DAYS . ' days')->format('Y-m-d H:i:s');
        $refreshExpiresAt = $now->modify('+' . self::REFRESH_TTL_DAYS . ' days')->format('Y-m-d H:i:s');

        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO member_api_tokens
                (member_id, club_id, identity_id, token_hash, refresh_token_hash,
                 device_info, user_agent, ip_address, app_version,
                 expires_at, refresh_expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $memberId,
            $clubId,
            $identityId,
            $accessHash,
            $refreshHash,
            $deviceInfo !== null ? substr($deviceInfo, 0, 255) : null,
            $userAgent !== null ? substr($userAgent, 0, 500) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $appVersion,
            $expiresAt,
            $refreshExpiresAt,
        ]);

        return [
            'access'             => $access,
            'refresh'            => $refresh,
            'expires_at'         => $expiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
            'token_id'           => (int)$db->lastInsertId(),
        ];
    }

    /**
     * Authenticate a raw bearer token. Returns ['member'=>..,'club'=>..,'token'=>..] or null.
     */
    public static function authenticate(?string $rawToken = null): ?array
    {
        if ($rawToken === null) {
            $rawToken = self::extractBearerToken();
        }
        if ($rawToken === null || $rawToken === '') {
            return null;
        }

        $hash = hash('sha256', $rawToken);
        $db   = Database::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM member_api_tokens
             WHERE token_hash = ?
               AND revoked_at IS NULL
               AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $member = (new MemberModel())->withoutScope()->findById((int)$row['member_id']);
        if (!$member || ($member['status'] ?? null) !== 'aktywny') {
            return null;
        }

        $clubStmt = $db->prepare("SELECT id, name, short_name, city, email FROM clubs WHERE id = ?");
        $clubStmt->execute([(int)$row['club_id']]);
        $club = $clubStmt->fetch() ?: null;

        // Best-effort touch — don't fail auth if the UPDATE fails.
        try {
            $db->prepare("UPDATE member_api_tokens SET last_used_at = NOW() WHERE id = ?")
               ->execute([(int)$row['id']]);
        } catch (\Throwable $e) {
            // ignore
        }

        // Activate club context (multi-tenant scoping for ClubScopedModel).
        ClubContext::set((int)$row['club_id']);

        return [
            'member' => $member,
            'club'   => $club,
            'token'  => $row,
        ];
    }

    /**
     * Authenticate or emit 401 JSON envelope and exit.
     *
     * @return array{member:array,club:?array,token:array}
     */
    public static function requireAuth(): array
    {
        $ctx = self::authenticate();
        if ($ctx === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'    => false,
                'error' => [
                    'code'    => 'unauthorized',
                    'message' => 'Brak lub nieprawidłowy token API.',
                ],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $ctx;
    }

    /**
     * Exchange a refresh token for a new access+refresh pair. Old row is revoked.
     *
     * @return array{access:string, refresh:string, expires_at:string, refresh_expires_at:string, token_id:int}|null
     */
    public static function refresh(string $rawRefresh): ?array
    {
        if ($rawRefresh === '') return null;
        $hash = hash('sha256', $rawRefresh);
        $db   = Database::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM member_api_tokens
             WHERE refresh_token_hash = ?
               AND revoked_at IS NULL
               AND (refresh_expires_at IS NULL OR refresh_expires_at > NOW())
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) return null;

        // Revoke old, issue new (rotation).
        $db->prepare("UPDATE member_api_tokens SET revoked_at = NOW() WHERE id = ?")
           ->execute([(int)$row['id']]);

        return self::issueToken(
            (int)$row['member_id'],
            (int)$row['club_id'],
            $row['device_info'] ?? null,
            $row['user_agent'] ?? null,
            $row['app_version'] ?? null,
            $row['identity_id'] !== null ? (int)$row['identity_id'] : null
        );
    }

    /** Soft-revoke by token DB id. */
    public static function revoke(int $tokenId): void
    {
        Database::pdo()
            ->prepare("UPDATE member_api_tokens SET revoked_at = NOW() WHERE id = ? AND revoked_at IS NULL")
            ->execute([$tokenId]);
    }

    /** Soft-revoke by raw token (used by /logout). */
    public static function revokeByRawToken(string $rawToken): bool
    {
        if ($rawToken === '') return false;
        $hash = hash('sha256', $rawToken);
        $stmt = Database::pdo()
            ->prepare("UPDATE member_api_tokens SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL");
        $stmt->execute([$hash]);
        return $stmt->rowCount() > 0;
    }

    /** Extract raw token from Authorization header. */
    public static function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if ($header === '' && function_exists('getallheaders')) {
            $all = getallheaders();
            foreach ($all as $k => $v) {
                if (strcasecmp($k, 'Authorization') === 0) {
                    $header = $v;
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
