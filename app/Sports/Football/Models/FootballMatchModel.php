<?php

namespace App\Sports\Football\Models;

use App\Models\ClubScopedModel;

class FootballMatchModel extends ClubScopedModel
{
    protected string $table = 'football_matches';

    public function listForClub(?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT fm.*, ht.name AS home_team_name
                FROM football_matches fm
                JOIN football_teams ht ON ht.id = fm.home_team_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND fm.club_id = ?"; $params[] = $clubId; }
        if ($status) { $sql .= " AND fm.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY fm.match_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function withDetails(int $id): ?array
    {
        $row = $this->findById($id);
        if (!$row) return null;

        $stmt = $this->db->prepare(
            "SELECT fme.*, m.first_name, m.last_name, m.member_number
             FROM football_match_events fme
             JOIN members m ON m.id = fme.member_id
             WHERE fme.match_id = ?
             ORDER BY fme.minute, fme.id"
        );
        $stmt->execute([$id]);
        $row['events'] = $stmt->fetchAll();

        $stmt = $this->db->prepare(
            "SELECT fl.*, m.first_name, m.last_name, m.member_number
             FROM football_lineups fl
             JOIN members m ON m.id = fl.member_id
             WHERE fl.match_id = ?
             ORDER BY fl.is_starter DESC, fl.position"
        );
        $stmt->execute([$id]);
        $row['lineup'] = $stmt->fetchAll();

        // Team name
        $stmt = $this->db->prepare("SELECT name FROM football_teams WHERE id = ?");
        $stmt->execute([$row['home_team_id']]);
        $row['home_team_name'] = $stmt->fetchColumn() ?: '?';

        return $row;
    }

    public function recentResults(int $limit = 5): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT fm.*, ht.name AS home_team_name
                FROM football_matches fm
                JOIN football_teams ht ON ht.id = fm.home_team_id
                WHERE fm.status = 'zakonczony'";
        if ($clubId !== null) $sql .= " AND fm.club_id = " . (int)$clubId;
        $sql .= " ORDER BY fm.match_date DESC LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll();
    }
}
