<?php

namespace App\Sports\Volleyball\Models;

use App\Models\ClubScopedModel;

class VolleyballTeamModel extends ClubScopedModel
{
    protected string $table = 'volleyball_teams';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT vt.*, ac.name AS age_cat_name, u.full_name AS coach_name
                FROM volleyball_teams vt
                LEFT JOIN age_categories ac ON ac.id = vt.age_category_id
                LEFT JOIN users u ON u.id = vt.coach_id
                WHERE vt.is_active = 1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND vt.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY vt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
