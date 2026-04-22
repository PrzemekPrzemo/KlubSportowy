<?php

namespace App\Sports\XcSki\Models;

use App\Models\ClubScopedModel;

class XcSkiResultModel extends ClubScopedModel
{
    protected string $table = 'xc_ski_results';

    public static array $TECHNIQUES = [
        'classic'        => 'Styl klasyczny',
        'skate'          => 'Styl dowolny (łyżwowy)',
        'pościg'         => 'Pościg (Skiathlon)',
        'masowy'         => 'Start masowy',
        'sprint_classic' => 'Sprint klasyczny',
        'sprint_skate'   => 'Sprint dowolny',
    ];

    public static function formatTime(?int $s): string
    {
        if ($s === null) return '—';
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;
        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $sec) : sprintf('%d:%02d', $m, $sec);
    }

    public function listForClub(?int $memberId = null, ?string $technique = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM xc_ski_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        if ($technique !== null && array_key_exists($technique, self::$TECHNIQUES)) {
            $sql .= " AND r.technique = ?"; $params[] = $technique;
        }
        $sql .= " ORDER BY r.event_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function bestFisPoints(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT technique, MIN(fis_points) AS best_fis
             FROM xc_ski_results
             WHERE club_id = ? AND member_id = ? AND fis_points IS NOT NULL
             GROUP BY technique"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
