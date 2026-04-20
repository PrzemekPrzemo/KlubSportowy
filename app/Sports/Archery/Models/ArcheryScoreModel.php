<?php

namespace App\Sports\Archery\Models;

use App\Models\ClubScopedModel;

class ArcheryScoreModel extends ClubScopedModel
{
    protected string $table = 'archery_scores';

    public static array $DISCIPLINES = [
        '18m'           => 'Hala 18m',
        '25m'           => 'Hala 25m',
        '50m'           => 'Plener 50m',
        '70m'           => 'Plener 70m',
        '90m'           => 'Plener 90m',
        'outdoor_50'    => 'Plener Olimpijski 50m',
        'outdoor_70'    => 'Plener Olimpijski 70m',
        'field'         => 'Terenowy (Field)',
        '3D'            => '3D',
        'recurve_open'  => 'Recurve Open',
        'compound_open' => 'Compound Open',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT as2.*, m.first_name, m.last_name, m.member_number
                FROM archery_scores as2
                JOIN members m ON m.id = as2.member_id
                WHERE as2.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND as2.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY as2.score_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
