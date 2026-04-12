<?php

namespace App\Models;

class DeviceTokenModel extends BaseModel
{
    protected string $table = 'device_tokens';

    public function register(int $memberId, string $token, string $platform = 'android'): void
    {
        $sql = "INSERT INTO device_tokens (member_id, token, platform, is_active)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE member_id = VALUES(member_id), platform = VALUES(platform), is_active = 1, updated_at = NOW()";
        $this->db->prepare($sql)->execute([$memberId, $token, $platform]);
    }

    public function unregister(string $token): void
    {
        $this->db->prepare("UPDATE device_tokens SET is_active = 0 WHERE token = ?")->execute([$token]);
    }

    public function tokensForMember(int $memberId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM device_tokens WHERE member_id = ? AND is_active = 1");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function tokensForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT dt.* FROM device_tokens dt
             JOIN members m ON m.id = dt.member_id
             WHERE m.club_id = ? AND dt.is_active = 1 AND m.status = 'aktywny'"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
