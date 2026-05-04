<?php

namespace App\Sports\Floorball\Models;

use App\Models\ClubScopedModel;

class FloorballMatchModel extends ClubScopedModel
{
    protected string $table = 'floorball_matches';

    public function schedule(?int $teamId = null, ?string $status = null): array
    {
        $sql    = "SELECT m.*,
                          ht.name AS home_team_name,
                          at.name AS away_team_name
                   FROM floorball_matches m
                   LEFT JOIN floorball_teams ht ON ht.id = m.home_team_id
                   LEFT JOIN floorball_teams at ON at.id = m.away_team_id
                   WHERE m.club_id = ?";
        $params = [$this->clubId()];

        if ($teamId !== null) {
            $sql .= " AND (m.home_team_id = ? OR m.away_team_id = ?)";
            $params[] = $teamId; $params[] = $teamId;
        }
        if ($status !== null) { $sql .= " AND m.status = ?"; $params[] = $status; }

        $sql .= " ORDER BY m.match_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function topScorers(int $teamId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.first_name, m.last_name, m.member_number,
                    SUM(CASE WHEN e.event_type IN ('gol','gol_pp','gol_sh') THEN 1 ELSE 0 END) AS goals,
                    SUM(CASE WHEN e.event_type = 'asysta' THEN 1 ELSE 0 END) AS assists,
                    SUM(CASE WHEN e.event_type IN ('kara_2min','kara_10min') THEN 1 ELSE 0 END) AS pim
             FROM floorball_events e
             JOIN floorball_players p ON p.id = e.player_id
             JOIN members m ON m.id = p.member_id
             WHERE p.team_id = ? AND e.club_id = ?
             GROUP BY p.member_id, m.first_name, m.last_name, m.member_number
             ORDER BY goals DESC, assists DESC
             LIMIT ?"
        );
        $stmt->execute([$teamId, $this->clubId(), $limit]);
        return $stmt->fetchAll();
    }

    public function addEvent(array $data): void
    {
        $this->db->prepare(
            "INSERT INTO floorball_events (club_id, match_id, player_id, event_type, minute)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $this->clubId(),
            (int)$data['match_id'],
            (int)$data['player_id'],
            $data['event_type'],
            $data['minute'] ?? null,
        ]);
    }

    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT SUM(CASE WHEN e.event_type IN ('gol','gol_pp','gol_sh') THEN 1 ELSE 0 END) AS goals,
                    SUM(CASE WHEN e.event_type = 'asysta' THEN 1 ELSE 0 END) AS assists,
                    SUM(CASE WHEN e.event_type IN ('kara_2min','kara_10min') THEN 1 ELSE 0 END) AS pim
             FROM floorball_events e
             JOIN floorball_players p ON p.id = e.player_id
             WHERE p.member_id = ? AND e.club_id = ?"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetch() ?: ['goals' => 0, 'assists' => 0, 'pim' => 0];
    }
}
