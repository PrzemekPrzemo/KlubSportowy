<?php

namespace App\Sports\Biathlon\Models;

use App\Models\ClubScopedModel;

class BiathlonResultModel extends ClubScopedModel
{
    protected string $table = 'biathlon_results';

    public static array $FORMATS = [
        'sprint'       => 'Sprint',
        'indywidualny' => 'Indywidualny',
        'pościg'       => 'Pościg',
        'masowy'       => 'Start masowy',
        'sztafeta'     => 'Sztafeta',
        'mikst'        => 'Mikst (mieszana)',
        'super_sprint' => 'Super Sprint',
    ];

    public static function formatTime(?int $s): string
    {
        if ($s === null) return '—';
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;
        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $sec) : sprintf('%d:%02d', $m, $sec);
    }

    public static function accuracy(?int $total, ?int $misses): ?float
    {
        if (!$total || $total === 0) return null;
        $hits = max(0, $total - ($misses ?? 0));
        return round(($hits / $total) * 100, 1);
    }

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM biathlon_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        $sql .= " ORDER BY r.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function accuracyStats(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(shootings_total), 0) AS total_shots,
                    COALESCE(SUM(misses_total), 0)    AS total_misses
             FROM biathlon_results
             WHERE club_id = ? AND member_id = ? AND shootings_total IS NOT NULL"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch() ?: ['total_shots' => 0, 'total_misses' => 0];
        $row['accuracy_pct'] = self::accuracy((int)$row['total_shots'], (int)$row['total_misses']);
        return $row;
    }
}
