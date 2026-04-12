<?php

namespace App\Models;

class DemoTokenModel extends BaseModel
{
    protected string $table = 'demo_tokens';

    /**
     * Create a new demo token for a club.
     */
    public function createToken(int $clubId, int $expiresInDays = 7, ?int $userId = null): string
    {
        $token = bin2hex(random_bytes(32));
        $this->insert([
            'token'      => $token,
            'club_id'    => $clubId,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days")),
            'created_by' => $userId,
        ]);
        return $token;
    }

    /**
     * Find a token record with club data joined.
     */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT dt.*, c.name AS club_name, c.city AS club_city
             FROM demo_tokens dt
             JOIN clubs c ON c.id = dt.club_id
             WHERE dt.token = ? AND dt.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * List all active (non-expired) demo tokens.
     */
    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT dt.*, c.name AS club_name, u.full_name AS creator_name
             FROM demo_tokens dt
             JOIN clubs c ON c.id = dt.club_id
             LEFT JOIN users u ON u.id = dt.created_by
             WHERE dt.expires_at > NOW()
             ORDER BY dt.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Delete expired tokens.
     */
    public function cleanup(): int
    {
        $stmt = $this->db->prepare("DELETE FROM demo_tokens WHERE expires_at <= NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
