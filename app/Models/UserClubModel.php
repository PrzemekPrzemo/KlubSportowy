<?php

namespace App\Models;

class UserClubModel extends BaseModel
{
    protected string $table = 'user_clubs';

    public function getForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT uc.*, c.name AS club_name
             FROM user_clubs uc
             JOIN clubs c ON c.id = uc.club_id
             WHERE uc.user_id = ? AND uc.is_active = 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT uc.*, u.username, u.full_name, u.email, u.is_active AS user_active
             FROM user_clubs uc
             JOIN users u ON u.id = uc.user_id
             WHERE uc.club_id = ?
             ORDER BY u.full_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function hasRoleInClub(int $userId, int $clubId, string $role): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM user_clubs WHERE user_id = ? AND club_id = ? AND role = ? AND is_active = 1"
        );
        $stmt->execute([$userId, $clubId, $role]);
        return (bool)$stmt->fetchColumn();
    }

    public function grantRole(int $userId, int $clubId, string $role): void
    {
        $sql = "INSERT IGNORE INTO user_clubs (user_id, club_id, role) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $clubId, $role]);
    }

    public function revokeRole(int $userId, int $clubId, string $role): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_clubs WHERE user_id = ? AND club_id = ? AND role = ?"
        );
        $stmt->execute([$userId, $clubId, $role]);
    }

    public function rolesForUserInClub(int $userId, int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT role FROM user_clubs WHERE user_id = ? AND club_id = ? AND is_active = 1"
        );
        $stmt->execute([$userId, $clubId]);
        return array_column($stmt->fetchAll(), 'role');
    }
}
