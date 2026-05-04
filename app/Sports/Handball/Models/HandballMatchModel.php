<?php

namespace App\Sports\Handball\Models;

use App\Models\ClubScopedModel;

class HandballMatchModel extends ClubScopedModel
{
    protected string $table = 'handball_matches';

    public static array $STATUSES = [
        'zaplanowany' => ['label' => 'Zaplanowany', 'class' => 'info'],
        'w_trakcie'   => ['label' => 'W trakcie',   'class' => 'warning'],
        'zakończony'  => ['label' => 'Zakończony',  'class' => 'success'],
        'odwołany'    => ['label' => 'Odwołany',    'class' => 'danger'],
    ];

    public function listForClub(?int $teamId = null, ?string $status = null): array
    {
        $sql = "SELECT hm.*, ht.name AS home_team_name
                FROM handball_matches hm
                JOIN handball_teams ht ON ht.id = hm.home_team_id
                WHERE hm.club_id = ?";
        $params = [$this->clubId()];

        if ($teamId !== null) {
            $sql .= " AND hm.home_team_id = ?";
            $params[] = $teamId;
        }
        if ($status !== null && array_key_exists($status, self::$STATUSES)) {
            $sql .= " AND hm.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY hm.match_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN event_type IN ('gol','7m_gol') THEN 1 ELSE 0 END) AS goals,
                SUM(CASE WHEN event_type = 'asysta' THEN 1 ELSE 0 END) AS assists,
                SUM(CASE WHEN event_type = '7m_gol' THEN 1 ELSE 0 END) AS seven_m_scored,
                SUM(CASE WHEN event_type = '7m_miss' THEN 1 ELSE 0 END) AS seven_m_missed,
                SUM(CASE WHEN event_type = 'żółta' THEN 1 ELSE 0 END) AS yellow_cards,
                SUM(CASE WHEN event_type = 'dwumin' THEN 1 ELSE 0 END) AS two_min,
                SUM(CASE WHEN event_type = 'czerwona' THEN 1 ELSE 0 END) AS red_cards
             FROM handball_events he
             JOIN handball_players hp ON hp.id = he.player_id
             WHERE he.club_id = ? AND hp.member_id = ?"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch();
        return $row ?: ['goals' => 0, 'assists' => 0, 'seven_m_scored' => 0, 'seven_m_missed' => 0,
                        'yellow_cards' => 0, 'two_min' => 0, 'red_cards' => 0];
    }

    public function upcoming(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT hm.*, ht.name AS home_team_name
             FROM handball_matches hm
             JOIN handball_teams ht ON ht.id = hm.home_team_id
             WHERE hm.club_id = ? AND hm.match_date >= NOW() AND hm.status = 'zaplanowany'
             ORDER BY hm.match_date ASC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
