<?php

namespace App\Sports\Chess\Models;

use App\Models\ClubScopedModel;

class ChessResultModel extends ClubScopedModel
{
    protected string $table = 'chess_results';

    public static array $CATEGORIES = [
        'classical'      => 'Klasyczne',
        'rapid'          => 'Szybkie',
        'blitz'          => 'Błyskawiczne',
        'bullet'         => 'Bullet',
        'correspondence' => 'Korespondencyjne',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT cr.*, m.first_name, m.last_name, m.member_number
                FROM chess_results cr
                JOIN members m ON m.id = cr.member_id
                WHERE cr.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND cr.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY cr.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
