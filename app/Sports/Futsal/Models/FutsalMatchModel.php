<?php

namespace App\Sports\Futsal\Models;

use App\Models\ClubScopedModel;

class FutsalMatchModel extends ClubScopedModel
{
    protected string $table = 'futsal_matches';

    public static array $STATUSES = [
        'zaplanowany' => ['label' => 'Zaplanowany', 'class' => 'info'],
        'w_trakcie'   => ['label' => 'W trakcie',   'class' => 'warning'],
        'zakonczony'  => ['label' => 'Zakończony',  'class' => 'success'],
        'odwolany'    => ['label' => 'Odwołany',    'class' => 'danger'],
    ];

    public function listForClub(?int $teamId = null): array
    {
        $sql = "SELECT m.*, t.name AS home_team_name
                FROM futsal_matches m
                JOIN futsal_teams t ON t.id = m.home_team_id
                WHERE m.club_id = ?";
        $params = [$this->clubId()];
        if ($teamId !== null) {
            $sql .= " AND m.home_team_id = ?";
            $params[] = $teamId;
        }
        $sql .= " ORDER BY m.match_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN event_type = 'gol' THEN 1 ELSE 0 END) AS goals,
                SUM(CASE WHEN event_type = 'asysta' THEN 1 ELSE 0 END) AS assists,
                SUM(CASE WHEN event_type = 'faul' THEN 1 ELSE 0 END) AS fouls,
                SUM(CASE WHEN event_type = 'zolta' THEN 1 ELSE 0 END) AS yellow_cards,
                SUM(CASE WHEN event_type = 'czerwona' THEN 1 ELSE 0 END) AS red_cards,
                SUM(CASE WHEN event_type = 'kara_2min' THEN 1 ELSE 0 END) AS blue_cards
             FROM futsal_events e
             JOIN futsal_players p ON p.id = e.player_id
             WHERE e.club_id = ? AND p.member_id = ?"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: [
            'goals' => 0, 'assists' => 0, 'fouls' => 0,
            'yellow_cards' => 0, 'red_cards' => 0, 'blue_cards' => 0,
        ];
    }

    public function topScorers(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.id AS member_id, m.first_name, m.last_name,
                    SUM(CASE WHEN e.event_type='gol' THEN 1 ELSE 0 END) AS goals,
                    SUM(CASE WHEN e.event_type='asysta' THEN 1 ELSE 0 END) AS assists
             FROM futsal_events e
             JOIN futsal_players p ON p.id = e.player_id
             JOIN members m ON m.id = p.member_id
             WHERE e.club_id = ?
             GROUP BY m.id, m.first_name, m.last_name
             HAVING goals > 0 OR assists > 0
             ORDER BY goals DESC, assists DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $this->clubId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
