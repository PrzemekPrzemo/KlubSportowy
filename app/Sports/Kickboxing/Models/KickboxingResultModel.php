<?php

namespace App\Sports\Kickboxing\Models;

use App\Models\ClubScopedModel;

class KickboxingResultModel extends ClubScopedModel
{
    protected string $table = 'kickboxing_results';

    public static array $STYLES = [
        'low_kick'            => 'Low kick',
        'K1'                  => 'K-1',
        'light_contact'       => 'Light contact',
        'kickboxing_punktowy' => 'Kickboxing punktowy',
        'muay_thai_polskie'   => 'Muay Thai Polskie',
        'full_contact'        => 'Full contact',
    ];

    public static array $RESULTS = [
        'W'  => ['label' => 'Wygrana',   'class' => 'success'],
        'L'  => ['label' => 'Porażka',   'class' => 'danger'],
        'D'  => ['label' => 'Remis',     'class' => 'secondary'],
        'NC' => ['label' => 'No Contest','class' => 'warning'],
        'DQ' => ['label' => 'DQ',         'class' => 'dark'],
    ];

    public static array $METHODS = [
        'KO'       => 'KO',
        'TKO'      => 'TKO',
        'points'   => 'Punkty',
        'decision' => 'Decyzja',
        'DQ'       => 'Dyskwalifikacja',
        'NC'       => 'No Contest',
    ];

    public function listForClub(?int $memberId = null, ?string $style = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM kickboxing_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        if ($style !== null && array_key_exists($style, self::$STYLES)) {
            $sql .= " AND r.style = ?"; $params[] = $style;
        }
        $sql .= " ORDER BY r.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function recordForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT result, COUNT(*) AS cnt
             FROM kickboxing_results
             WHERE club_id = ? AND member_id = ? AND result IS NOT NULL
             GROUP BY result"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $rec = ['W' => 0, 'L' => 0, 'D' => 0, 'NC' => 0, 'DQ' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $rec[$r['result']] = (int)$r['cnt'];
        }
        return $rec;
    }
}
