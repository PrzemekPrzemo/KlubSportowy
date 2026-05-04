<?php

namespace App\Sports\Sambo\Models;

use App\Models\ClubScopedModel;

class SamboResultModel extends ClubScopedModel
{
    protected string $table = 'sambo_results';

    public static array $STYLES = [
        'sport_sambo'    => 'Sambo sportowe',
        'combat_sambo'   => 'Sambo bojowe',
        'freestyle_sambo' => 'Freestyle Sambo',
        'beach_sambo'    => 'Sambo plażowe',
    ];

    public static array $WEIGHT_CLASSES = [
        '-52', '-57', '-62', '-68', '-74', '-82', '-90', '-100', '+100',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sr.*, m.first_name, m.last_name, m.member_number
                FROM sambo_results sr
                JOIN members m ON m.id = sr.member_id
                WHERE sr.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND sr.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY sr.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
