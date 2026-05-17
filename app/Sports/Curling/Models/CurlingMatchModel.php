<?php

namespace App\Sports\Curling\Models;

use App\Models\ClubScopedModel;

class CurlingMatchModel extends ClubScopedModel
{
    protected string $table = 'curling_matches';

    public static array $STATUSES = [
        'zaplanowany' => ['label' => 'Zaplanowany', 'class' => 'info'],
        'w_trakcie'   => ['label' => 'W trakcie',   'class' => 'warning'],
        'zakonczony'  => ['label' => 'Zakończony',  'class' => 'success'],
        'odwolany'    => ['label' => 'Odwołany',    'class' => 'danger'],
    ];

    public function listForClub(?int $teamId = null): array
    {
        $sql = "SELECT m.*, t.name AS home_team_name
                FROM curling_matches m
                JOIN curling_teams t ON t.id = m.home_team_id
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

    /**
     * Agregaty per member dla curlera — liczy ends played (poprzez team membership)
     * oraz srednie punkty zdobyte/stracone.
     */
    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(DISTINCT e.match_id) AS matches_played,
                COALESCE(SUM(e.home_score),0) AS points_scored,
                COALESCE(SUM(e.away_score),0) AS points_against,
                COUNT(e.id) AS ends_played
             FROM sport_curling_match_ends e
             JOIN curling_matches m ON m.id = e.match_id
             JOIN curling_players p ON p.team_id = m.home_team_id
             WHERE p.member_id = ? AND e.club_id = ?"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetch() ?: [
            'matches_played' => 0, 'points_scored' => 0,
            'points_against' => 0, 'ends_played' => 0,
        ];
    }
}
