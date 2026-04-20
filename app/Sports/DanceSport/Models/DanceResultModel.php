<?php

namespace App\Sports\DanceSport\Models;

use App\Models\ClubScopedModel;

class DanceResultModel extends ClubScopedModel
{
    protected string $table = 'dance_results';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT dr.*,
                    ml.first_name AS leader_first, ml.last_name AS leader_last,
                    mf.first_name AS follower_first, mf.last_name AS follower_last
             FROM dance_results dr
             JOIN members ml ON ml.id = dr.leader_id
             LEFT JOIN dance_couples dc ON dc.id = dr.couple_id
             LEFT JOIN members mf ON mf.id = dc.follower_id
             WHERE dr.club_id = ?
             ORDER BY dr.competition_date DESC, ml.last_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
