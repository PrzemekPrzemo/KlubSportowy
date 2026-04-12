<?php

namespace App\Models;

class FacilityModel extends ClubScopedModel
{
    protected string $table = 'facilities';

    /**
     * Lista aktywnych obiektów dla klubu.
     */
    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM facilities WHERE is_active = 1";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
