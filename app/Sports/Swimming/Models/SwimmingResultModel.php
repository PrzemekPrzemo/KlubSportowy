<?php

namespace App\Sports\Swimming\Models;

use App\Models\ClubScopedModel;

class SwimmingResultModel extends ClubScopedModel
{
    protected string $table = 'swimming_results';

    public static array $STROKES = [
        'freestyle'      => 'Dowolny (Freestyle)',
        'backstroke'     => 'Grzbietowy',
        'breaststroke'   => 'Żabka (Breaststroke)',
        'butterfly'      => 'Motylek (Butterfly)',
        'medley'         => 'Stylem zmiennym (Medley)',
        'relay_freestyle' => 'Sztafeta dowolna',
        'relay_medley'   => 'Sztafeta zmiennym',
    ];

    public static array $DISTANCES = [25, 50, 100, 200, 400, 800, 1500, 3000, 5000, 10000];

    public static array $POOL_TYPES = [
        '25m'        => 'Basen 25m (krótki)',
        '50m'        => 'Basen 50m (olimpijski)',
        'open_water' => 'Woda otwarta',
    ];

    /**
     * Formats milliseconds as m:ss.cc (minutes:seconds.centiseconds).
     */
    public static function formatTime(int $ms): string
    {
        $totalCs = (int)round($ms / 10);
        $cs      = $totalCs % 100;
        $totalSec = (int)($totalCs / 100);
        $sec     = $totalSec % 60;
        $min     = (int)($totalSec / 60);

        return sprintf('%d:%02d.%02d', $min, $sec, $cs);
    }

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sr.*, m.first_name, m.last_name, m.member_number
                FROM swimming_results sr
                JOIN members m ON m.id = sr.member_id
                WHERE sr.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND sr.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY sr.score_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Returns the best (lowest) time_ms for a member+stroke+distance combination, or null if none.
     */
    public function bestTime(int $memberId, string $stroke, int $distanceM): ?int
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT MIN(time_ms) FROM swimming_results
             WHERE club_id = ? AND member_id = ? AND stroke = ? AND distance_m = ?"
        );
        $stmt->execute([$clubId, $memberId, $stroke, $distanceM]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : null;
    }

    /**
     * Returns one record per stroke+distance combination with best (lowest) time for a member.
     */
    public function personalBests(int $memberId): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sr.stroke, sr.distance_m, sr.pool_type, sr.time_ms, sr.score_date,
                       sr.competition_name, sr.id,
                       m.first_name, m.last_name
                FROM swimming_results sr
                JOIN members m ON m.id = sr.member_id
                WHERE sr.club_id = ?
                  AND sr.member_id = ?
                  AND sr.time_ms = (
                      SELECT MIN(sr2.time_ms)
                      FROM swimming_results sr2
                      WHERE sr2.club_id    = sr.club_id
                        AND sr2.member_id  = sr.member_id
                        AND sr2.stroke     = sr.stroke
                        AND sr2.distance_m = sr.distance_m
                  )
                ORDER BY sr.stroke, sr.distance_m";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetchAll();
    }
}
