<?php

namespace App\Sports\Kayaking\Models;

use App\Models\ClubScopedModel;

class KayakResultModel extends ClubScopedModel
{
    protected string $table = 'kayak_results';

    public static array $DISCIPLINES = [
        'sprint'        => 'Sprint (flatwater)',
        'długi_dystans' => 'Długi dystans',
        'slalom'        => 'Slalom',
        'maraton'       => 'Maraton',
        'rafting'       => 'Rafting',
        'dragon_boat'   => 'Dragon boat',
    ];

    public static function formatTime(?int $ms): string
    {
        if ($ms === null) return '—';
        $totalCs = (int)round($ms / 10);
        $cs  = $totalCs % 100;
        $sec = intdiv($totalCs, 100) % 60;
        $min = intdiv($totalCs, 6000);
        return sprintf('%d:%02d.%02d', $min, $sec, $cs);
    }

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number,
                       b.name AS boat_name, b.boat_type
                FROM kayak_results r
                JOIN members m ON m.id = r.member_id
                LEFT JOIN kayak_boats b ON b.id = r.boat_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        $sql .= " ORDER BY r.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function personalBests(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT discipline, distance_m, MIN(time_ms) AS best_time_ms
             FROM kayak_results
             WHERE club_id = ? AND member_id = ? AND time_ms IS NOT NULL
             GROUP BY discipline, distance_m
             ORDER BY discipline, distance_m"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
