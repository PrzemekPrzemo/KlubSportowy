<?php

namespace App\Models;

class DisciplineModel extends BaseModel
{
    protected string $table = 'disciplines';

    /** Dyscypliny globalne + per-klub dla danego sportu. */
    public function listForSport(int $sportId, ?int $clubId = null): array
    {
        $sql    = "SELECT * FROM `disciplines` WHERE sport_id = ? AND is_active = 1
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
