<?php

namespace App\Sports\Football\Models;

use App\Models\ClubScopedModel;

class FootballTeamModel extends ClubScopedModel
{
    protected string $table = 'football_teams';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ft.*, ac.name AS age_cat_name, u.full_name AS coach_name
                FROM football_teams ft
                LEFT JOIN age_categories ac ON ac.id = ft.age_category_id
                LEFT JOIN users u ON u.id = ft.coach_id
                WHERE ft.is_active = 1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ft.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY ft.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
