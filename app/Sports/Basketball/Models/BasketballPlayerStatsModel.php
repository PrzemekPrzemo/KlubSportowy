<?php

namespace App\Sports\Basketball\Models;

use App\Models\BaseModel;

class BasketballPlayerStatsModel extends BaseModel
{
    protected string $table = 'basketball_player_stats';

    public function forMatch(int $matchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT bps.*, m.first_name, m.last_name, m.member_number
             FROM basketball_player_stats bps
             JOIN members m ON m.id = bps.member_id
             WHERE bps.match_id = ?
             ORDER BY bps.points DESC, m.last_name"
        );
        $stmt->execute([$matchId]);
        return $stmt->fetchAll();
    }

    public function topScorers(int $clubId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.first_name, m.last_name, m.member_number,
                    COUNT(bps.id) AS games,
                    SUM(bps.points) AS total_points,
                    ROUND(AVG(bps.points), 1) AS avg_points,
                    SUM(bps.three_pointers) AS total_threes
             FROM basketball_player_stats bps
             JOIN members m ON m.id = bps.member_id
             JOIN basketball_matches bm ON bm.id = bps.match_id
             WHERE bm.club_id = ? AND bm.status = 'zakonczony'
             GROUP BY bps.member_id
             ORDER BY total_points DESC
             LIMIT ?"
        );
        $stmt->execute([$clubId, $limit]);
        return $stmt->fetchAll();
    }

    public function topAssists(int $clubId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.first_name, m.last_name, m.member_number,
                    COUNT(bps.id) AS games,
                    SUM(bps.assists) AS total_assists,
                    ROUND(AVG(bps.assists), 1) AS avg_assists
             FROM basketball_player_stats bps
             JOIN members m ON m.id = bps.member_id
             JOIN basketball_matches bm ON bm.id = bps.match_id
             WHERE bm.club_id = ? AND bm.status = 'zakonczony'
             GROUP BY bps.member_id
             ORDER BY total_assists DESC
             LIMIT ?"
        );
        $stmt->execute([$clubId, $limit]);
        return $stmt->fetchAll();
    }

    public function topRebounders(int $clubId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.first_name, m.last_name, m.member_number,
                    COUNT(bps.id) AS games,
                    SUM(bps.rebounds) AS total_rebounds,
                    ROUND(AVG(bps.rebounds), 1) AS avg_rebounds
             FROM basketball_player_stats bps
             JOIN members m ON m.id = bps.member_id
             JOIN basketball_matches bm ON bm.id = bps.match_id
             WHERE bm.club_id = ? AND bm.status = 'zakonczony'
             GROUP BY bps.member_id
             ORDER BY total_rebounds DESC
             LIMIT ?"
        );
        $stmt->execute([$clubId, $limit]);
        return $stmt->fetchAll();
    }
}
