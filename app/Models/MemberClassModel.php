<?php

namespace App\Models;

class MemberClassModel extends BaseModel
{
    protected string $table = 'member_classes';

    public function listForSport(int $sportId, ?int $clubId = null): array
    {
        $sql    = "SELECT * FROM `member_classes` WHERE sport_id = ? AND is_active = 1
                   AND (club_id IS NULL";
        $params = [$sportId];
        if ($clubId !== null) {
            $sql     .= " OR club_id = ?";
            $params[] = $clubId;
        }
        $sql .= ") ORDER BY sort_order, name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
