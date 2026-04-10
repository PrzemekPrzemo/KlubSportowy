<?php

namespace App\Models;

class ClubSettingsModel extends BaseModel
{
    protected string $table = 'club_settings';

    public function get(int $clubId, string $key, mixed $default = null): mixed
    {
        $stmt = $this->db->prepare(
            "SELECT value FROM `club_settings` WHERE club_id = ? AND `key` = ?"
        );
        $stmt->execute([$clubId, $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public function set(int $clubId, string $key, mixed $value, string $type = 'text', string $label = ''): void
    {
        $sql = "INSERT INTO `club_settings` (club_id, `key`, value, `type`, label)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value), `type` = VALUES(`type`), label = VALUES(label)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $key, (string)$value, $type, $label]);
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
