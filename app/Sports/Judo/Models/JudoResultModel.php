<?php

namespace App\Sports\Judo\Models;

use App\Models\ClubScopedModel;

class JudoResultModel extends ClubScopedModel
{
    protected string $table = 'judo_results';

    public static array $WEIGHT_CLASSES = [
        '-46','-50','-55','-60','-66','-73','-81','-90','-100','+100','open'
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT jr.*, m.first_name, m.last_name, m.member_number
                FROM judo_results jr
                JOIN members m ON m.id = jr.member_id
                WHERE jr.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND jr.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY jr.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
