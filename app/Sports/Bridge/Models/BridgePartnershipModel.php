<?php

namespace App\Sports\Bridge\Models;

use App\Models\ClubScopedModel;

class BridgePartnershipModel extends ClubScopedModel
{
    protected string $table = 'bridge_partnerships';

    public static array $CATEGORIES = [
        'open'      => 'Open',
        'kobiety'   => 'Kobiety',
        'mixed'     => 'Mikst',
        'juniorzy'  => 'Juniorzy',
        'seniorzy'  => 'Seniorzy',
    ];

    public function listForClub(bool $activeOnly = false): array
    {
        $sql = "SELECT p.*,
                       p1.first_name AS p1_first, p1.last_name AS p1_last,
                       p2.first_name AS p2_first, p2.last_name AS p2_last
                FROM bridge_partnerships p
                JOIN members p1 ON p1.id = p.player1_id
                JOIN members p2 ON p2.id = p.player2_id
                WHERE p.club_id = ?";
        if ($activeOnly) $sql .= " AND p.active = 1";
        $sql .= " ORDER BY p.active DESC, p1.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function partnershipsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    p1.first_name AS p1_first, p1.last_name AS p1_last,
                    p2.first_name AS p2_first, p2.last_name AS p2_last
             FROM bridge_partnerships p
             JOIN members p1 ON p1.id = p.player1_id
             JOIN members p2 ON p2.id = p.player2_id
             WHERE p.club_id = ?
               AND (p.player1_id = ? OR p.player2_id = ?)
             ORDER BY p.active DESC"
        );
        $stmt->execute([$this->clubId(), $memberId, $memberId]);
        return $stmt->fetchAll();
    }
}
