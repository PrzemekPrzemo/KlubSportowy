<?php

namespace App\Sports\FieldHockey\Models;

use App\Models\ClubScopedModel;

class FieldHockeyMatchModel extends ClubScopedModel
{
    protected string $table = 'field_hockey_matches';

    public static array $STATUSES = [
        'zaplanowany' => ['label' => 'Zaplanowany', 'class' => 'info'],
        'w_trakcie'   => ['label' => 'W trakcie',   'class' => 'warning'],
        'zakończony'  => ['label' => 'Zakończony',  'class' => 'success'],
        'odwołany'    => ['label' => 'Odwołany',    'class' => 'danger'],
    ];

    public function listForClub(?int $teamId = null): array
    {
        $sql = "SELECT fm.*, ft.name AS home_team_name
                FROM field_hockey_matches fm
                JOIN field_hockey_teams ft ON ft.id = fm.home_team_id
                WHERE fm.club_id = ?";
        $params = [$this->clubId()];
        if ($teamId !== null) { $sql .= " AND fm.home_team_id = ?"; $params[] = $teamId; }
        $sql .= " ORDER BY fm.match_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN event_type = 'gol'      THEN 1 ELSE 0 END) AS goals,
                SUM(CASE WHEN event_type = 'asysta'   THEN 1 ELSE 0 END) AS assists,
                SUM(CASE WHEN event_type = 'PC'       THEN 1 ELSE 0 END) AS penalty_corners,
                SUM(CASE WHEN event_type = 'PS'       THEN 1 ELSE 0 END) AS penalty_strokes,
                SUM(CASE WHEN event_type = 'żółta'    THEN 1 ELSE 0 END) AS yellow_cards,
                SUM(CASE WHEN event_type = 'zielona'  THEN 1 ELSE 0 END) AS green_cards,
                SUM(CASE WHEN event_type = 'czerwona' THEN 1 ELSE 0 END) AS red_cards
             FROM field_hockey_events fe
             JOIN field_hockey_players fp ON fp.id = fe.player_id
             WHERE fe.club_id = ? AND fp.member_id = ?"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch();
        return $row ?: ['goals' => 0, 'assists' => 0, 'penalty_corners' => 0, 'penalty_strokes' => 0, 'yellow_cards' => 0, 'green_cards' => 0, 'red_cards' => 0];
    }
}
