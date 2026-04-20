<?php

namespace App\Sports\Squash\Models;

use App\Models\ClubScopedModel;

class SquashResultModel extends ClubScopedModel
{
    protected string $table = 'squash_results';

    public static array $CATEGORIES = [
        'singles'        => 'Singel',
        'doubles'        => 'Debel',
        'mixed_doubles'  => 'Mikst',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sr.*,
                       m.first_name, m.last_name, m.member_number,
                       (sr.psa_ranking_after - sr.psa_ranking_before) AS ranking_delta
                FROM squash_results sr
                JOIN members m ON m.id = sr.member_id
                WHERE sr.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND sr.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY sr.match_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
