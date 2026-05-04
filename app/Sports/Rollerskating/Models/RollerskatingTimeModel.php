<?php

namespace App\Sports\Rollerskating\Models;

use App\Models\ClubScopedModel;

class RollerskatingTimeModel extends ClubScopedModel
{
    protected string $table = 'rollerskating_times';

    public static array $SKATING_STYLES = [
        'short_track'      => 'Short track',
        'long_track'       => 'Long track',
        'inline_speed'     => 'Inline speed',
        'inline_freestyle' => 'Inline freestyle',
        'artistic'         => 'Jazda artystyczna',
        'hockey'           => 'Hokej na wrotkach',
        'other'            => 'Inne',
    ];

    public function listForClub(?string $distance = null, int $page = 1, int $perPage = 25): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT rt.*, m.first_name, m.last_name, m.member_number, d.name AS discipline_name
                FROM rollerskating_times rt
                JOIN members m ON m.id = rt.member_id
                LEFT JOIN disciplines d ON d.id = rt.discipline_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND rt.club_id = ?"; $params[] = $clubId; }
        if ($distance) { $sql .= " AND rt.distance = ?"; $params[] = $distance; }
        $sql .= " ORDER BY rt.time_ms ASC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function rankings(?string $distance = null, int $limit = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT rt.*, m.first_name, m.last_name
                FROM rollerskating_times rt
                JOIN members m ON m.id = rt.member_id
                WHERE rt.is_personal_best = 1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND rt.club_id = ?"; $params[] = $clubId; }
        if ($distance) { $sql .= " AND rt.distance = ?"; $params[] = $distance; }
        $sql .= " ORDER BY rt.time_ms ASC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Formatuje milisekundy na czytelny czas */
    public static function formatTime(int $ms): string
    {
        $s = intdiv($ms, 1000);
        $rem = $ms % 1000;
        $m = intdiv($s, 60);
        $s = $s % 60;
        if ($m > 0) {
            return sprintf('%d:%02d.%03d', $m, $s, $rem);
        }
        return sprintf('%d.%03d', $s, $rem);
    }
}
