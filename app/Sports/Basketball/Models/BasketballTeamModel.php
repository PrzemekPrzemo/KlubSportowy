<?php

namespace App\Sports\Basketball\Models;

use App\Models\ClubScopedModel;

class BasketballTeamModel extends ClubScopedModel
{
    protected string $table = 'basketball_teams';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT bt.*, ac.name AS age_cat_name, u.full_name AS coach_name
                FROM basketball_teams bt
                LEFT JOIN age_categories ac ON ac.id = bt.age_category_id
                LEFT JOIN users u ON u.id = bt.coach_id
                WHERE bt.is_active = 1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND bt.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY bt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
