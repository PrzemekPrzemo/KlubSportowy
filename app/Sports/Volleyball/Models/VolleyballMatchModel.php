<?php

namespace App\Sports\Volleyball\Models;

use App\Models\ClubScopedModel;

class VolleyballMatchModel extends ClubScopedModel
{
    protected string $table = 'volleyball_matches';

    public function listForClub(?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT vm.*, ht.name AS home_team_name
                FROM volleyball_matches vm
                JOIN volleyball_teams ht ON ht.id = vm.home_team_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND vm.club_id = ?"; $params[] = $clubId; }
        if ($status) { $sql .= " AND vm.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY vm.match_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function withDetails(int $id): ?array
    {
        $row = $this->findById($id);
        if (!$row) return null;

        // Player stats
        $stmt = $this->db->prepare(
            "SELECT vps.*, m.first_name, m.last_name, m.member_number
             FROM volleyball_player_stats vps
             JOIN members m ON m.id = vps.member_id
             WHERE vps.match_id = ?
             ORDER BY vps.kills DESC, m.last_name"
        );
        $stmt->execute([$id]);
        $row['player_stats'] = $stmt->fetchAll();

        // Team name
        $stmt = $this->db->prepare("SELECT name FROM volleyball_teams WHERE id = ?");
        $stmt->execute([$row['home_team_id']]);
        $row['home_team_name'] = $stmt->fetchColumn() ?: '?';

        return $row;
    }
}
