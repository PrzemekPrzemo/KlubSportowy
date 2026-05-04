<?php

namespace App\Sports\Golf\Models;

use App\Models\ClubScopedModel;

class GolfRoundModel extends ClubScopedModel
{
    protected string $table = 'golf_rounds';

    public static array $TEES = [
        'white'  => ['label' => 'White (białe)',  'color' => '#ffffff', 'text' => '#333'],
        'yellow' => ['label' => 'Yellow (żółte)', 'color' => '#ffd700', 'text' => '#333'],
        'blue'   => ['label' => 'Blue (niebieskie)', 'color' => '#0d6efd', 'text' => '#fff'],
        'red'    => ['label' => 'Red (czerwone)', 'color' => '#dc3545', 'text' => '#fff'],
        'black'  => ['label' => 'Black (czarne)', 'color' => '#000000', 'text' => '#fff'],
        'green'  => ['label' => 'Green (zielone)','color' => '#28a745', 'text' => '#fff'],
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM golf_rounds r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        $sql .= " ORDER BY r.round_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function bestScore(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT course_name, round_date, total_strokes, net_score
             FROM golf_rounds
             WHERE club_id = ? AND member_id = ? AND net_score IS NOT NULL
             ORDER BY net_score ASC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
