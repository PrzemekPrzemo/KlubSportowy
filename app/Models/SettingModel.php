<?php

namespace App\Models;

class SettingModel extends BaseModel
{
    protected string $table = 'settings';

    public function get(string $key, mixed $default = null): mixed
    {
        $stmt = $this->db->prepare("SELECT value FROM `settings` WHERE `key` = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $sql = "INSERT INTO settings (`key`, value, label)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key, (string)$value, $key]);
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM `settings` ORDER BY `key`")->fetchAll();
    }
}
