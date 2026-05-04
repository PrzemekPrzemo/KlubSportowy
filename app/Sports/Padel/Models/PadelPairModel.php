<?php

namespace App\Sports\Padel\Models;

use App\Models\ClubScopedModel;

class PadelPairModel extends ClubScopedModel
{
    protected string $table = 'padel_pairs';

    public function listForClub(?string $category = null): array
    {
        $sql    = "SELECT p.*,
                          m1.first_name AS p1_first, m1.last_name AS p1_last,
                          m2.first_name AS p2_first, m2.last_name AS p2_last
                   FROM padel_pairs p
                   JOIN members m1 ON m1.id = p.player1_id
                   JOIN members m2 ON m2.id = p.player2_id
                   WHERE p.club_id = ?";
        $params = [$this->clubId()];
        if ($category !== null) { $sql .= " AND p.category = ?"; $params[] = $category; }
        $sql .= " ORDER BY p.ranking_points DESC, p.pair_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function pairsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    m1.first_name AS p1_first, m1.last_name AS p1_last,
                    m2.first_name AS p2_first, m2.last_name AS p2_last
             FROM padel_pairs p
             JOIN members m1 ON m1.id = p.player1_id
             JOIN members m2 ON m2.id = p.player2_id
             WHERE p.club_id = ? AND (p.player1_id = ? OR p.player2_id = ?)
             ORDER BY p.ranking_points DESC"
        );
        $stmt->execute([$this->clubId(), $memberId, $memberId]);
        return $stmt->fetchAll();
    }
}
