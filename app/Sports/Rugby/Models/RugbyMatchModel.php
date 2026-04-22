<?php

namespace App\Sports\Rugby\Models;

use App\Models\ClubScopedModel;

class RugbyMatchModel extends ClubScopedModel
{
    protected string $table = 'rugby_matches';

    public static array $STATUSES = [
        'zaplanowany' => ['label' => 'Zaplanowany', 'class' => 'info'],
        'w_trakcie'   => ['label' => 'W trakcie',   'class' => 'warning'],
        'zakończony'  => ['label' => 'Zakończony',  'class' => 'success'],
        'odwołany'    => ['label' => 'Odwołany',    'class' => 'danger'],
    ];

    public static array $EVENT_POINTS = [
        'przyłożenie'   => 5,
        'podwyższenie'  => 2,
        'karny'         => 3,
        'drop'          => 3,
    ];

    public function listForClub(?int $teamId = null): array
    {
        $sql = "SELECT rm.*, rt.name AS home_team_name
                FROM rugby_matches rm
                JOIN rugby_teams rt ON rt.id = rm.home_team_id
                WHERE rm.club_id = ?";
        $params = [$this->clubId()];
        if ($teamId !== null) {
            $sql .= " AND rm.home_team_id = ?";
            $params[] = $teamId;
        }
        $sql .= " ORDER BY rm.match_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN event_type = 'przyłożenie' THEN 1 ELSE 0 END) AS tries,
                SUM(CASE WHEN event_type = 'podwyższenie' THEN 1 ELSE 0 END) AS conversions,
                SUM(CASE WHEN event_type = 'karny' THEN 1 ELSE 0 END) AS penalties,
                SUM(CASE WHEN event_type = 'drop' THEN 1 ELSE 0 END) AS drop_goals,
                SUM(CASE WHEN event_type = 'żółta' THEN 1 ELSE 0 END) AS yellow_cards,
                SUM(CASE WHEN event_type = 'czerwona' THEN 1 ELSE 0 END) AS red_cards,
                COALESCE(SUM(points), 0) AS total_points
             FROM rugby_events re
             JOIN rugby_players rp ON rp.id = re.player_id
             WHERE re.club_id = ? AND rp.member_id = ?"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch();
        return $row ?: ['tries' => 0, 'conversions' => 0, 'penalties' => 0, 'drop_goals' => 0, 'yellow_cards' => 0, 'red_cards' => 0, 'total_points' => 0];
    }
}
