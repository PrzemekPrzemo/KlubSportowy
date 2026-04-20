<?php

namespace App\Sports\Bjj\Models;

use App\Models\ClubScopedModel;

class BjjResultModel extends ClubScopedModel
{
    protected string $table = 'bjj_results';

    public static array $WEIGHT_CATEGORIES = [
        '-49','-55','-62','-69','-77','-85','-94','-100','+100','open',
        // kids
        '-25','-30','-35','-40','-45',
    ];

    public function listForClub(?int $memberId = null, ?string $gi = null, ?int $year = null): array
    {
        $sql    = "SELECT r.*, m.first_name, m.last_name, m.member_number
                   FROM bjj_results r
                   JOIN members m ON m.id = r.member_id
                   WHERE r.club_id = ?";
        $params = [$this->clubId()];

        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        if ($gi !== null)       { $sql .= " AND r.gi = ?";        $params[] = $gi; }
        if ($year !== null)     { $sql .= " AND YEAR(r.event_date) = ?"; $params[] = $year; }

        $sql .= " ORDER BY r.event_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT result, COUNT(*) as cnt FROM bjj_results
             WHERE club_id = ? AND member_id = ?
             GROUP BY result"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $rows = $stmt->fetchAll();
        $stats = ['win' => 0, 'loss' => 0, 'draw' => 0, 'dq' => 0];
        foreach ($rows as $r) {
            $stats[$r['result']] = (int)$r['cnt'];
        }
        return $stats;
    }
}
