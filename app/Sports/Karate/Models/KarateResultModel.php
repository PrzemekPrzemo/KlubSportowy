<?php

namespace App\Sports\Karate\Models;

use App\Models\ClubScopedModel;

class KarateResultModel extends ClubScopedModel
{
    protected string $table = 'karate_results';

    public static array $WEIGHT_CLASSES = [
        '-50','-55','-60','-67','-75','-84','+84','open'
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT kr.*, m.first_name, m.last_name, m.member_number
                FROM karate_results kr
                JOIN members m ON m.id = kr.member_id
                WHERE kr.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND kr.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY kr.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
