<?php

namespace App\Models;

class ErrorLogModel extends BaseModel
{
    protected string $table = 'error_log';

    public function listFiltered(?string $level, ?string $from, ?string $to, int $page = 1, int $perPage = 30): array
    {
        $where = [];
        $params = [];

        if ($level !== null && $level !== '') {
            $where[] = 'e.level = ?';
            $params[] = $level;
        }
        if ($from !== null && $from !== '') {
            $where[] = 'e.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $where[] = 'e.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT e.*, u.username, u.full_name, c.name AS club_name
                FROM error_log e
                LEFT JOIN users u ON u.id = e.user_id
                LEFT JOIN clubs c ON c.id = e.club_id
                {$whereSql}
                ORDER BY e.created_at DESC, e.id DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function stats(): array
    {
        $out = [];
        foreach (['24 HOUR', '7 DAY', '30 DAY'] as $range) {
            $stmt = $this->db->prepare(
                "SELECT level, COUNT(*) AS c FROM error_log
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$range})
                 GROUP BY level"
            );
            $stmt->execute();
            $counts = ['debug' => 0, 'info' => 0, 'warning' => 0, 'error' => 0, 'critical' => 0];
            foreach ($stmt->fetchAll() as $row) {
                $counts[$row['level']] = (int)$row['c'];
            }
            $out[strtolower(str_replace(' ', '_', $range))] = $counts;
        }
        return $out;
    }

    public function purgeOlderThan(int $days): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM error_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}
