<?php

namespace App\Models;

class UserModel extends BaseModel
{
    protected string $table = 'users';

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->insert($data);
    }

    public function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password']);
    }

    public function touchLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE `users` SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }

    /** Zwraca wszystkie kluby + role użytkownika. */
    public function getClubsForUser(int $userId): array
    {
        $sql = "SELECT uc.id, uc.club_id, uc.role, uc.is_active, c.name, c.short_name, c.city
                FROM user_clubs uc
                JOIN clubs c ON c.id = uc.club_id
                WHERE uc.user_id = ? AND uc.is_active = 1 AND c.is_active = 1
                ORDER BY c.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
