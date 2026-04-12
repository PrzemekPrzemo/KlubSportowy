<?php

namespace App\Sports\Rollerskating\Models;

use App\Models\ClubScopedModel;

class RollerskatingEquipmentModel extends ClubScopedModel
{
    protected string $table = 'rollerskating_equipment';

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT re.*, m.first_name, m.last_name
                FROM rollerskating_equipment re
                LEFT JOIN members m ON m.id = re.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND re.club_id = ?"; $params[] = $clubId; }
        if ($memberId !== null) { $sql .= " AND re.member_id = ?"; $params[] = $memberId; }
        $sql .= " ORDER BY re.type, re.brand";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
