<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::pdo();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $dir     = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $stmt = $this->db->query("SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$dir}");
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function count(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM `{$this->table}`")->fetchColumn();
    }

    public function insert(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $stmt  = $this->db->prepare("INSERT INTO `{$this->table}` (`{$cols}`) VALUES ({$holds})");
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) return true;
        $set  = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET {$set} WHERE id = ?");
        return $stmt->execute([...array_values($data), $id]);
    }

    public function getDb(): PDO
    {
        return $this->db;
    }

    protected function paginate(string $sql, array $params, int $page, int $perPage = 20): array
    {
        $countSql  = "SELECT COUNT(*) FROM ({$sql}) AS _count_wrap";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt   = $this->db->prepare($sql . " LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }
}
