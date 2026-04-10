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
}
