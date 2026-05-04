<?php

namespace App\Sports\Gymnastics\Models;

use App\Models\ClubScopedModel;

class GymnasticsResultModel extends ClubScopedModel
{
    protected string $table = 'gymnastics_results';

    public static array $DISCIPLINES = ['artystyczna', 'rytmiczna', 'akrobatyczna', 'trampolina'];

    public function listForClub(?string $discipline = null, ?int $memberId = null): array
    {
        $sql    = "SELECT r.*, m.first_name, m.last_name, m.member_number
                   FROM gymnastics_results r
                   JOIN members m ON m.id = r.member_id
                   WHERE r.club_id = ?";
        $params = [$this->clubId()];

        if ($discipline !== null) { $sql .= " AND r.discipline = ?"; $params[] = $discipline; }
        if ($memberId   !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }

        $sql .= " ORDER BY r.event_date DESC, r.total_score DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function topScores(string $discipline, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name
             FROM gymnastics_results r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ? AND r.discipline = ?
             ORDER BY r.total_score DESC LIMIT ?"
        );
        $stmt->execute([$this->clubId(), $discipline, $limit]);
        return $stmt->fetchAll();
    }
}
