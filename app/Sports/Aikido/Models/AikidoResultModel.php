<?php

namespace App\Sports\Aikido\Models;

use App\Models\ClubScopedModel;

class AikidoResultModel extends ClubScopedModel
{
    protected string $table = 'aikido_results';

    public static array $CATEGORIES = [
        'taigi'        => 'Taigi (formy)',
        'randori'      => 'Randori (walka)',
        'tanto_randori' => 'Tanto Randori',
        'embu'         => 'Embu (pokaz)',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ar.*, m.first_name, m.last_name, m.member_number
                FROM aikido_results ar
                JOIN members m ON m.id = ar.member_id
                WHERE ar.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND ar.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY ar.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
