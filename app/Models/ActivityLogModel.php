<?php

namespace App\Models;

use App\Helpers\Auth;
use App\Helpers\ClubContext;

class ActivityLogModel extends BaseModel
{
    protected string $table = 'activity_log';

    public function log(string $action, ?string $entity = null, ?int $entityId = null, ?string $details = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO activity_log (club_id, user_id, action, entity, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            ClubContext::current(),
            Auth::id(),
            $action,
            $entity,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public function recent(int $limit = 50): array
    {
        $sql = "SELECT al.*, u.username, u.full_name, c.name AS club_name
                FROM activity_log al
                LEFT JOIN users u ON u.id = al.user_id
                LEFT JOIN clubs c ON c.id = al.club_id
                ORDER BY al.created_at DESC
                LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll();
    }

    public function listFiltered(
        ?int $clubId,
        ?int $userId,
        ?string $action,
        ?string $from,
        ?string $to,
        int $page = 1,
        int $perPage = 50
    ): array {
        $where = [];
        $params = [];

        if ($clubId !== null) {
            $where[] = 'al.club_id = ?';
            $params[] = $clubId;
        }
        if ($userId !== null) {
            $where[] = 'al.user_id = ?';
            $params[] = $userId;
        }
        if ($action !== null && $action !== '') {
            $where[] = 'al.action LIKE ?';
            $params[] = '%' . $action . '%';
        }
        if ($from !== null && $from !== '') {
            $where[] = 'al.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $where[] = 'al.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT al.*, u.username, u.full_name, c.name AS club_name
                FROM activity_log al
                LEFT JOIN users u ON u.id = al.user_id
                LEFT JOIN clubs c ON c.id = al.club_id
                {$whereSql}
                ORDER BY al.created_at DESC, al.id DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function distinctActions(int $limit = 50): array
    {
        $stmt = $this->db->query(
            "SELECT action, COUNT(*) AS c FROM activity_log
             GROUP BY action ORDER BY c DESC LIMIT " . (int)$limit
        );
        return $stmt->fetchAll();
    }
}
