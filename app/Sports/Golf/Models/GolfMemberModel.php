<?php

declare(strict_types=1);

namespace App\Sports\Golf\Models;

use App\Models\ClubScopedModel;

/**
 * sport_golf_member — profil zawodnika z handicapem (PZG-like).
 */
class GolfMemberModel extends ClubScopedModel
{
    protected string $table = 'sport_golf_member';

    public const MAX_HCP = 54.0;
    public const MIN_HCP = -5.0;

    public function findByMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE member_id = ? AND club_id = ?"
        );
        $stmt->execute([$memberId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $memberId, array $data): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) return false;

        $hcp = isset($data['hcp']) ? max(self::MIN_HCP, min(self::MAX_HCP, (float)$data['hcp'])) : 36.0;
        $hcpUpd = !empty($data['hcp_updated_at']) ? (string)$data['hcp_updated_at'] : date('Y-m-d');
        $pga = isset($data['pga_license']) && $data['pga_license'] !== '' ? (string)$data['pga_license'] : null;

        if ($this->findByMember($memberId)) {
            $stmt = $this->db->prepare(
                "UPDATE `{$this->table}`
                    SET hcp=?, hcp_updated_at=?, pga_license=?
                  WHERE member_id=? AND club_id=?"
            );
            return $stmt->execute([$hcp, $hcpUpd, $pga, $memberId, $clubId]);
        }
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (member_id, club_id, hcp, hcp_updated_at, pga_license)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$memberId, $clubId, $hcp, $hcpUpd, $pga]);
    }

    /**
     * Uproszczona formuła PZG (WHS-like): nowy HCP = avg(8 najlepszych Score Differentials z 20 ostatnich).
     * Score Differential = (113 / slope) * (strokes - rating).
     *
     * @param array<int, array{total_strokes:int|null,handicap_used?:float|null,rating?:float|null,slope?:int|null}> $scorecards
     */
    public static function computeWhsLike(array $scorecards): ?float
    {
        $diffs = [];
        foreach ($scorecards as $sc) {
            $strokes = (int)($sc['total_strokes'] ?? 0);
            $rating  = (float)($sc['rating'] ?? 72.0);
            $slope   = (int)($sc['slope'] ?? 113);
            if ($strokes <= 0 || $slope <= 0) continue;
            $diffs[] = (113.0 / $slope) * ($strokes - $rating);
        }
        if (count($diffs) < 3) return null;
        sort($diffs);
        $take = (int)floor(count($diffs) * 0.4); // ~best 40%
        $take = max(1, min($take, 8));
        $best = array_slice($diffs, 0, $take);
        $avg = array_sum($best) / count($best);
        return round(max(self::MIN_HCP, min(self::MAX_HCP, $avg)), 1);
    }
}
