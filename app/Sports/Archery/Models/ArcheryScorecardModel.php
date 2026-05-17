<?php

declare(strict_types=1);

namespace App\Sports\Archery\Models;

use App\Models\ClubScopedModel;

/**
 * sport_archery_scorecards — pelne rundy strzeleckie z totalami 10s/Xs.
 */
class ArcheryScorecardModel extends ClubScopedModel
{
    protected string $table = 'sport_archery_scorecards';

    public const DISTANCES = [18, 25, 30, 50, 60, 70, 90];

    public function listForClub(?bool $verifiedOnly = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sas.*, m.first_name, m.last_name, m.member_number
                  FROM `{$this->table}` sas
                  JOIN members m ON m.id = sas.member_id
                 WHERE sas.club_id = ?";
        $params = [$clubId];
        if ($verifiedOnly === true)  { $sql .= " AND sas.verified = 1"; }
        if ($verifiedOnly === false) { $sql .= " AND sas.verified = 0"; }
        $sql .= " ORDER BY sas.shot_at DESC, sas.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT sas.*
               FROM `{$this->table}` sas
              WHERE sas.club_id = ? AND sas.member_id = ?
           ORDER BY sas.shot_at DESC"
        );
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetchAll();
    }

    public function verify(int $id, int $verifiedBy): bool
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
                SET verified = 1, verified_by = ?, verified_at = NOW()
              WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$verifiedBy, $id, $clubId]);
    }

    /**
     * Liczy total_score / tens / x_count z tablicy endów.
     * Wartosci dozwolone: 0..10 oraz 'X' (traktowane jako 10 + x_count).
     *
     * @param array<int, array<int, int|string>> $ends
     * @return array{total_score:int,tens:int,x_count:int,arrows_total:int}
     */
    public static function computeTotals(array $ends): array
    {
        $total = $tens = $xs = $arrows = 0;
        foreach ($ends as $end) {
            foreach ($end as $arrow) {
                $arrows++;
                if (is_string($arrow) && strtoupper($arrow) === 'X') {
                    $total += 10; $tens++; $xs++;
                    continue;
                }
                $v = (int)$arrow;
                if ($v < 0) $v = 0;
                if ($v > 10) $v = 10;
                $total += $v;
                if ($v === 10) $tens++;
            }
        }
        return [
            'total_score'   => $total,
            'tens'          => $tens,
            'x_count'       => $xs,
            'arrows_total'  => $arrows,
        ];
    }
}
