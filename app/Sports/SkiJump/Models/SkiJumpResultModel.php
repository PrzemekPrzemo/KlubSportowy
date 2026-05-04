<?php

namespace App\Sports\SkiJump\Models;

use App\Models\ClubScopedModel;

class SkiJumpResultModel extends ClubScopedModel
{
    protected string $table = 'ski_jump_results';

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM ski_jump_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        $sql .= " ORDER BY r.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function longestJump(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT event_name, event_date, venue, hill_k,
                    GREATEST(COALESCE(jump1_m, 0), COALESCE(jump2_m, 0)) AS longest_m
             FROM ski_jump_results
             WHERE club_id = ? AND member_id = ?
             ORDER BY longest_m DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
