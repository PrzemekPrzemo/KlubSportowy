<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

class ClimbingAttemptModel extends ClubScopedModel
{
    protected string $table = 'sport_climbing_attempts';

    public static array $RESULTS = [
        'top'     => 'Top (zaliczone)',
        'zone'    => 'Strefa',
        'failed'  => 'Nie zaliczone',
        'flash'   => 'Flash',
        'onsight' => 'On-sight',
    ];

    public function listForClub(?int $memberId = null, int $limit = 100): array
    {
        $sql = "SELECT a.*, r.route_name, r.discipline, r.grade_french, r.grade_yds,
                       m.first_name, m.last_name, m.member_number
                FROM sport_climbing_attempts a
                JOIN sport_climbing_routes r ON r.id = a.route_id
                JOIN members m ON m.id = a.member_id
                WHERE a.club_id = ?";
        $params = [$this->clubId()];
        if ($memberId !== null) {
            $sql .= " AND a.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY a.attempt_date DESC, a.id DESC LIMIT " . max(1, (int)$limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function topsCount(int $memberId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sport_climbing_attempts
             WHERE club_id = ? AND member_id = ? AND result IN ('top','flash','onsight')"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return (int)$stmt->fetchColumn();
    }
}
