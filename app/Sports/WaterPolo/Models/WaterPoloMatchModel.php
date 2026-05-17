<?php

namespace App\Sports\WaterPolo\Models;

use App\Models\ClubScopedModel;

class WaterPoloMatchModel extends ClubScopedModel
{
    protected string $table = 'water_polo_matches';

    public static array $STATUSES = [
        'zaplanowany' => ['label' => 'Zaplanowany', 'class' => 'info'],
        'w_trakcie'   => ['label' => 'W trakcie',   'class' => 'warning'],
        'zakonczony'  => ['label' => 'Zakończony',  'class' => 'success'],
        'odwolany'    => ['label' => 'Odwołany',    'class' => 'danger'],
    ];

    public function listForClub(?int $teamId = null): array
    {
        $sql = "SELECT m.*, t.name AS home_team_name
                FROM water_polo_matches m
                JOIN water_polo_teams t ON t.id = m.home_team_id
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
                SUM(CASE WHEN event_type = 'wykluczenie' THEN 1 ELSE 0 END) AS exclusions,
                SUM(CASE WHEN event_type = 'wykluczenie_5' THEN 1 ELSE 0 END) AS exclusions_5,
                SUM(CASE WHEN event_type = 'obrona_brm' THEN 1 ELSE 0 END) AS saves,
                SUM(CASE WHEN event_type = 'rzut_karny' THEN 1 ELSE 0 END) AS penalties
             FROM water_polo_events e
             JOIN water_polo_players p ON p.id = e.player_id
             WHERE e.club_id = ? AND p.member_id = ?"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: [
            'goals' => 0, 'assists' => 0,
            'exclusions' => 0, 'exclusions_5' => 0,
            'saves' => 0, 'penalties' => 0,
        ];
    }

    public function topScorers(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.id AS member_id, m.first_name, m.last_name,
                    SUM(CASE WHEN e.event_type='gol' THEN 1 ELSE 0 END) AS goals
             FROM water_polo_events e
             JOIN water_polo_players p ON p.id = e.player_id
             JOIN members m ON m.id = p.member_id
             WHERE e.club_id = ?
             GROUP BY m.id, m.first_name, m.last_name
             HAVING goals > 0
             ORDER BY goals DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $this->clubId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
