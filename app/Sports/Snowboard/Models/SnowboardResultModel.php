<?php

namespace App\Sports\Snowboard\Models;

use App\Models\ClubScopedModel;

class SnowboardResultModel extends ClubScopedModel
{
    protected string $table = 'snowboard_results';

    public static array $DISCIPLINES = [
        'slalom'          => 'Slalom SB',
        'gigant'          => 'Slalom gigant SB',
        'halfpipe'        => 'Halfpipe',
        'slopestyle'      => 'Slopestyle',
        'big_air'         => 'Big Air',
        'boardercross'    => 'Boardercross',
        'snowboardcross'  => 'Snowboardcross (SBX)',
        'parallel_slalom' => 'Parallel Slalom (PSL)',
    ];

    public function listForClub(?int $memberId = null, ?string $discipline = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM snowboard_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        if ($discipline !== null && array_key_exists($discipline, self::$DISCIPLINES)) {
            $sql .= " AND r.discipline = ?"; $params[] = $discipline;
        }
        $sql .= " ORDER BY r.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function bestScorePerDiscipline(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT discipline, MAX(best_score) AS max_score
             FROM snowboard_results
             WHERE club_id = ? AND member_id = ? AND best_score IS NOT NULL
             GROUP BY discipline"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
