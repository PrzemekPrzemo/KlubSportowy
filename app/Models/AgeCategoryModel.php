<?php

namespace App\Models;

class AgeCategoryModel extends BaseModel
{
    protected string $table = 'age_categories';

    /** Kategorie globalne + dla danego sportu i klubu. */
    public function listAvailable(?int $sportId = null, ?int $clubId = null): array
    {
        $sql    = "SELECT * FROM `age_categories` WHERE 1=1";
        $params = [];

        $conds = ['(sport_id IS NULL OR sport_id = ?)'];
        $params[] = $sportId;

        $conds[] = '(club_id IS NULL OR club_id = ?)';
        $params[] = $clubId;

        $sql .= ' AND ' . implode(' AND ', $conds);
        $sql .= ' ORDER BY sort_order, age_from';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
