<?php

namespace App\Sports\Shooting\Models;

use App\Models\ClubScopedModel;

class JudgeLicenseModel extends ClubScopedModel
{
    protected string $table = 'judge_licenses';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT jl.*, m.first_name, m.last_name, m.member_number,
                       DATEDIFF(jl.valid_until, CURDATE()) AS days_remaining
                FROM judge_licenses jl
                JOIN members m ON m.id = jl.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND jl.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY jl.class DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
