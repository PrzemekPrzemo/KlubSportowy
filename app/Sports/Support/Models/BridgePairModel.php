<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

class BridgePairModel extends ClubScopedModel
{
    protected string $table = 'sport_bridge_pairs';

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    mn.first_name AS north_first, mn.last_name AS north_last,
                    ms.first_name AS south_first, ms.last_name AS south_last
             FROM sport_bridge_pairs p
             JOIN members mn ON mn.id = p.member_north_id
             JOIN members ms ON ms.id = p.member_south_id
             WHERE p.club_id = ?
             ORDER BY p.masterpoints DESC, p.id DESC"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function ranking(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    mn.last_name AS north_last, mn.first_name AS north_first,
                    ms.last_name AS south_last, ms.first_name AS south_first
             FROM sport_bridge_pairs p
             JOIN members mn ON mn.id = p.member_north_id
             JOIN members ms ON ms.id = p.member_south_id
             WHERE p.club_id = ?
             ORDER BY p.masterpoints DESC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function addMasterpoints(int $pairId, float $delta): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sport_bridge_pairs
             SET masterpoints = masterpoints + ?
             WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$delta, $pairId, $this->clubId()]);
    }
}
