<?php

namespace App\Sports\AlpineSki\Models;

use App\Models\ClubScopedModel;

class AlpineSkiResultModel extends ClubScopedModel
{
    protected string $table = 'alpine_ski_results';

    public static array $DISCIPLINES = [
        'slalom'               => 'Slalom (SL)',
        'slalom_gigant'        => 'Slalom gigant (GS)',
        'supergigant'          => 'Supergigant (SG)',
        'zjazd'                => 'Zjazd (DH)',
        'kombinacja'           => 'Kombinacja',
        'kombinacja_alpejska'  => 'Kombinacja alpejska',
    ];

    public static function formatMs(?int $ms): string
    {
        if ($ms === null) return '—';
        $totalCs = (int)round($ms / 10);
        $cs  = $totalCs % 100;
        $sec = intdiv($totalCs, 100) % 60;
        $min = intdiv($totalCs, 6000);
        return sprintf('%d:%02d.%02d', $min, $sec, $cs);
    }

    public function listForClub(?int $memberId = null, ?string $discipline = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM alpine_ski_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        if ($discipline !== null && array_key_exists($discipline, self::$DISCIPLINES)) {
            $sql .= " AND r.discipline = ?"; $params[] = $discipline;
        }
        $sql .= " ORDER BY r.event_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function bestFisPoints(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT discipline, MIN(fis_points) AS best_fis
             FROM alpine_ski_results
             WHERE club_id = ? AND member_id = ? AND fis_points IS NOT NULL
             GROUP BY discipline"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
