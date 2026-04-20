<?php

namespace App\Sports\Equestrian\Models;

use App\Models\ClubScopedModel;

class EquestrianResultModel extends ClubScopedModel
{
    protected string $table = 'equestrian_results';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT er.*, m.first_name, m.last_name, h.name AS horse_name
             FROM equestrian_results er
             JOIN members m ON m.id = er.member_id
             LEFT JOIN equestrian_horses h ON h.id = er.horse_id
             WHERE er.club_id = ?
             ORDER BY er.competition_date DESC, m.last_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
