<?php

namespace App\Sports\IceHockey\Models;

use App\Models\ClubScopedModel;

class IceHockeyMatchModel extends ClubScopedModel
{
    protected string $table = 'icehockey_matches';

    public static array $STATUSES = [
        'zaplanowany' => ['label' => 'Zaplanowany', 'class' => 'info'],
        'w_trakcie'   => ['label' => 'W trakcie',   'class' => 'warning'],
        'zakończony'  => ['label' => 'Zakończony',  'class' => 'success'],
        'odwołany'    => ['label' => 'Odwołany',    'class' => 'danger'],
    ];

    public function listForClub(?int $teamId = null): array
    {
        $sql = "SELECT hm.*, ht.name AS home_team_name,
                       (hm.p1_home + hm.p2_home + hm.p3_home + hm.ot_home + hm.so_home) AS total_home,
                       (hm.p1_away + hm.p2_away + hm.p3_away + hm.ot_away + hm.so_away) AS total_away
                FROM icehockey_matches hm
                JOIN icehockey_teams ht ON ht.id = hm.home_team_id
                WHERE hm.club_id = ?";
        $params = [$this->clubId()];
        if ($teamId !== null) {
            $sql .= " AND hm.home_team_id = ?";
            $params[] = $teamId;
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
                SUM(CASE WHEN event_type IN ('gol','gol_pp','gol_sh','gol_en') THEN 1 ELSE 0 END) AS goals,
                SUM(CASE WHEN event_type = 'asysta' THEN 1 ELSE 0 END) AS assists,
                SUM(CASE WHEN event_type = 'kara_2'  THEN 2  ELSE 0 END) +
                SUM(CASE WHEN event_type = 'kara_5'  THEN 5  ELSE 0 END) +
                SUM(CASE WHEN event_type = 'kara_10' THEN 10 ELSE 0 END) AS pim,
                SUM(CASE WHEN event_type = 'gol_pp' THEN 1 ELSE 0 END) AS pp_goals,
                SUM(CASE WHEN event_type = 'gol_sh' THEN 1 ELSE 0 END) AS sh_goals
             FROM icehockey_events he
             JOIN icehockey_players hp ON hp.id = he.player_id
             WHERE he.club_id = ? AND hp.member_id = ?"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch() ?: [];
        $row['points'] = (int)($row['goals'] ?? 0) + (int)($row['assists'] ?? 0);
        return $row;
    }
}
