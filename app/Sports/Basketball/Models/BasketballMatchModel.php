<?php

namespace App\Sports\Basketball\Models;

use App\Models\ClubScopedModel;

class BasketballMatchModel extends ClubScopedModel
{
    protected string $table = 'basketball_matches';

    public function listForClub(?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT bm.*, ht.name AS home_team_name
                FROM basketball_matches bm
                JOIN basketball_teams ht ON ht.id = bm.home_team_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND bm.club_id = ?"; $params[] = $clubId; }
        if ($status) { $sql .= " AND bm.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY bm.match_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function withDetails(int $id): ?array
    {
        $row = $this->findById($id);
        if (!$row) return null;

        $stmt = $this->db->prepare(
            "SELECT bps.*, m.first_name, m.last_name, m.member_number
             FROM basketball_player_stats bps
             JOIN members m ON m.id = bps.member_id
             WHERE bps.match_id = ?
             ORDER BY bps.points DESC, m.last_name"
        );
        $stmt->execute([$id]);
        $row['player_stats'] = $stmt->fetchAll();

        // Team name
        $stmt = $this->db->prepare("SELECT name FROM basketball_teams WHERE id = ?");
        $stmt->execute([$row['home_team_id']]);
        $row['home_team_name'] = $stmt->fetchColumn() ?: '?';

        return $row;
    }

    public function recentResults(int $limit = 5): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT bm.*, ht.name AS home_team_name
                FROM basketball_matches bm
                JOIN basketball_teams ht ON ht.id = bm.home_team_id
                WHERE bm.status = 'zakonczony'";
        if ($clubId !== null) $sql .= " AND bm.club_id = " . (int)$clubId;
        $sql .= " ORDER BY bm.match_date DESC LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll();
    }
}
