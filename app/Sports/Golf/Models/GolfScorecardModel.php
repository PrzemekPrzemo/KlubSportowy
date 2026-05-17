<?php

declare(strict_types=1);

namespace App\Sports\Golf\Models;

use App\Models\ClubScopedModel;

/**
 * sport_golf_scorecards — scorecardy 18-dolkowe (JSON) + flag verified.
 */
class GolfScorecardModel extends ClubScopedModel
{
    protected string $table = 'sport_golf_scorecards';

    public function listForClub(?bool $verifiedOnly = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sgs.*, m.first_name, m.last_name, m.member_number,
                       c.name AS course_name, c.par_total
                  FROM `{$this->table}` sgs
                  JOIN members m ON m.id = sgs.member_id
             LEFT JOIN sport_golf_courses c ON c.id = sgs.course_id
                 WHERE sgs.club_id = ?";
        $params = [$clubId];
        if ($verifiedOnly === true)  { $sql .= " AND sgs.verified = 1"; }
        if ($verifiedOnly === false) { $sql .= " AND sgs.verified = 0"; }
        $sql .= " ORDER BY sgs.played_at DESC, sgs.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT sgs.*, c.name AS course_name, c.par_total
               FROM `{$this->table}` sgs
          LEFT JOIN sport_golf_courses c ON c.id = sgs.course_id
              WHERE sgs.club_id = ? AND sgs.member_id = ?
           ORDER BY sgs.played_at DESC"
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
     * Liczy total_strokes + total_to_par z tablicy strzalow per dolek vs par kursu.
     *
     * @param int[] $holeScores
     * @param int   $courseParTotal
     * @return array{total_strokes:int,total_to_par:int}
     */
    public static function computeTotals(array $holeScores, int $courseParTotal): array
    {
        $total = 0;
        foreach ($holeScores as $s) {
            $total += (int)$s;
        }
        return [
            'total_strokes' => $total,
            'total_to_par'  => $total - $courseParTotal,
        ];
    }
}
