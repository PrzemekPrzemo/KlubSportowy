<?php

namespace App\Sports\Football\Models;

use App\Models\BaseModel;

class FootballStatsModel extends BaseModel
{
    protected string $table = 'football_match_events';

    public function topScorers(int $clubId, int $limit = 10): array
    {
        return $this->topByEventType($clubId, 'gol', 'total_goals', $limit);
    }

    public function topAssists(int $clubId, int $limit = 10): array
    {
        return $this->topByEventType($clubId, 'asysta', 'total_assists', $limit);
    }

    public function topYellowCards(int $clubId, int $limit = 10): array
    {
        return $this->topByEventType($clubId, 'zolta_kartka', 'total_yellow', $limit);
    }

    public function topRedCards(int $clubId, int $limit = 10): array
    {
        return $this->topByEventType($clubId, 'czerwona_kartka', 'total_red', $limit);
    }

    public function summary(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(DISTINCT fm.id)                                          AS matches_finished,
                SUM(CASE WHEN fme.type = 'gol'             THEN 1 ELSE 0 END)  AS goals,
                SUM(CASE WHEN fme.type = 'asysta'          THEN 1 ELSE 0 END)  AS assists,
                SUM(CASE WHEN fme.type = 'zolta_kartka'    THEN 1 ELSE 0 END)  AS yellow_cards,
                SUM(CASE WHEN fme.type = 'czerwona_kartka' THEN 1 ELSE 0 END)  AS red_cards
             FROM football_matches fm
             LEFT JOIN football_match_events fme ON fme.match_id = fm.id
             WHERE fm.club_id = ? AND fm.status = 'zakonczony'"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch() ?: [];
        return array_map(fn($v) => (int)($v ?? 0), $row);
    }

    private function topByEventType(int $clubId, string $eventType, string $alias, int $limit): array
    {
        $sql =
            "SELECT m.id AS member_id, m.first_name, m.last_name, m.member_number,
                    COUNT(DISTINCT fme.match_id) AS games,
                    COUNT(fme.id)                AS {$alias}
             FROM football_match_events fme
             JOIN football_matches fm ON fm.id = fme.match_id
             JOIN members          m  ON m.id  = fme.member_id
             WHERE fm.club_id = ? AND fm.status = 'zakonczony' AND fme.type = ?
             GROUP BY fme.member_id
             ORDER BY {$alias} DESC
             LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $eventType]);
        return $stmt->fetchAll();
    }
}
