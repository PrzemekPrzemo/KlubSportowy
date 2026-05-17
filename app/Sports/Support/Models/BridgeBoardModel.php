<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

class BridgeBoardModel extends ClubScopedModel
{
    protected string $table = 'sport_bridge_boards';

    public function listForClub(?int $tournamentId = null, int $limit = 200): array
    {
        $sql = "SELECT b.*,
                       p.pair_name,
                       mn.last_name AS north_last, mn.first_name AS north_first,
                       ms.last_name AS south_last, ms.first_name AS south_first
                FROM sport_bridge_boards b
                JOIN sport_bridge_pairs p ON p.id = b.pair_id
                JOIN members mn ON mn.id = p.member_north_id
                JOIN members ms ON ms.id = p.member_south_id
                WHERE b.club_id = ?";
        $params = [$this->clubId()];
        if ($tournamentId !== null) {
            $sql .= " AND b.tournament_id = ?";
            $params[] = $tournamentId;
        }
        $sql .= " ORDER BY b.played_at DESC, b.board_number ASC LIMIT " . max(1, (int)$limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
