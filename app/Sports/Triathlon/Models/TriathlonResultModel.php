<?php

namespace App\Sports\Triathlon\Models;

use App\Models\ClubScopedModel;

class TriathlonResultModel extends ClubScopedModel
{
    protected string $table = 'triathlon_results';

    public static array $DISTANCES = ['super_sprint', 'sprint', 'olympic', 'half', 'full'];

    public static function formatTime(?int $seconds): string
    {
        if ($seconds === null) return '—';
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    public function listForClub(?int $memberId = null, ?string $distance = null, ?int $year = null): array
    {
        $sql    = "SELECT r.*, m.first_name, m.last_name, m.member_number
                   FROM triathlon_results r
                   JOIN members m ON m.id = r.member_id
                   WHERE r.club_id = ?";
        $params = [$this->clubId()];

        if ($memberId  !== null) { $sql .= " AND r.member_id = ?";       $params[] = $memberId; }
        if ($distance  !== null) { $sql .= " AND r.distance_type = ?";   $params[] = $distance; }
        if ($year      !== null) { $sql .= " AND YEAR(r.event_date) = ?"; $params[] = $year; }

        $sql .= " ORDER BY r.event_date DESC, r.total_time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function pbsForMember(int $memberId): array
    {
        $pbs = [];
        foreach (self::$DISTANCES as $dist) {
            $stmt = $this->db->prepare(
                "SELECT * FROM triathlon_results
                 WHERE club_id=? AND member_id=? AND distance_type=? AND dnf=0 AND dns=0
                 ORDER BY total_time ASC LIMIT 1"
            );
            $stmt->execute([$this->clubId(), $memberId, $dist]);
            $row = $stmt->fetch();
            if ($row) $pbs[$dist] = $row;
        }
        return $pbs;
    }
}
