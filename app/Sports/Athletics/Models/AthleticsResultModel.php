<?php

namespace App\Sports\Athletics\Models;

use App\Models\ClubScopedModel;
use PDO;

class AthleticsResultModel extends ClubScopedModel
{
    protected string $table = 'athletics_results';

    public static array $COMMON_DISCIPLINES = [
        'Biegi krótkie' => ['60m', '100m', '200m', '400m'],
        'Biegi średnie' => ['800m', '1500m', '3000m'],
        'Biegi długie'  => ['5000m', '10000m', 'maraton', 'półmaraton'],
        'Biegi z przeszkodami' => ['60m ppł', '100m ppł', '110m ppł', '400m ppł', '3000m z przeszkodami'],
        'Skoki'         => ['skok wzwyż', 'skok o tyczce', 'skok w dal', 'trójskok'],
        'Rzuty'         => ['pchnięcie kulą', 'rzut dyskiem', 'rzut młotem', 'rzut oszczepem'],
        'Wieloboje'     => ['dziesięciobój', 'siedmiobój', 'pięciobój'],
        'Chód'          => ['chód 20km', 'chód 50km', 'chód 5km'],
    ];

    public static array $UNITS = [
        's'   => 'sekundy (s)',
        'min' => 'minuty (min)',
        'm'   => 'metry (m)',
        'cm'  => 'centymetry (cm)',
        'kg'  => 'kilogramy (kg)',
        'pts' => 'punkty (wielobój)',
    ];

    public function listForClub(?string $discipline = null, int $page = 1, int $perPage = 30): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT ar.*, m.first_name, m.last_name, m.member_number
                   FROM athletics_results ar
                   JOIN members m ON m.id = ar.member_id
                   WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ar.club_id = ?"; $params[] = $clubId; }
        if ($discipline)      { $sql .= " AND ar.discipline_name = ?"; $params[] = $discipline; }
        $sql .= " ORDER BY ar.competition_date DESC, ar.discipline_name";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function disciplines(): array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT DISTINCT discipline_name FROM athletics_results
             WHERE club_id = ? ORDER BY discipline_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function formatResult(float $value, string $unit): string
    {
        if ($unit === 's') {
            if ($value >= 60) {
                $m  = (int)($value / 60);
                $s  = fmod($value, 60);
                return sprintf('%d:%05.2f', $m, $s);
            }
            return number_format($value, 2);
        }
        if ($unit === 'min') {
            $m = (int)$value;
            $s = ($value - $m) * 60;
            return sprintf('%d:%05.2f', $m, $s);
        }
        return number_format($value, 2) . ' ' . $unit;
    }
}
