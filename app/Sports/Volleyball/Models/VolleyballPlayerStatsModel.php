<?php

namespace App\Sports\Volleyball\Models;

use App\Models\BaseModel;

class VolleyballPlayerStatsModel extends BaseModel
{
    protected string $table = 'volleyball_player_stats';

    public function forMatch(int $matchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT vps.*, m.first_name, m.last_name, m.member_number
             FROM volleyball_player_stats vps
             JOIN members m ON m.id = vps.member_id
             WHERE vps.match_id = ?
             ORDER BY vps.kills DESC, m.last_name"
        );
        $stmt->execute([$matchId]);
        return $stmt->fetchAll();
    }

    public function topPlayers(int $clubId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.id, m.first_name, m.last_name, m.member_number,
                    COUNT(vps.id) AS matches_played,
                    SUM(vps.kills) AS total_kills,
                    SUM(vps.blocks) AS total_blocks,
                    SUM(vps.aces) AS total_aces,
                    SUM(vps.attacks) AS total_attacks,
                    SUM(vps.digs) AS total_digs,
                    SUM(vps.errors) AS total_errors,
                    SUM(vps.serves) AS total_serves
             FROM volleyball_player_stats vps
             JOIN members m ON m.id = vps.member_id
             JOIN volleyball_matches vm ON vm.id = vps.match_id
             WHERE vm.club_id = ?
             GROUP BY m.id, m.first_name, m.last_name, m.member_number
             ORDER BY total_kills DESC
             LIMIT ?"
        );
        $stmt->execute([$clubId, $limit]);
        return $stmt->fetchAll();
    }

    public function topByColumn(int $clubId, string $column, int $limit = 10): array
    {
        $allowed = ['kills', 'blocks', 'aces', 'attacks', 'digs', 'serves'];
        if (!in_array($column, $allowed, true)) {
            $column = 'kills';
        }
        $stmt = $this->db->prepare(
            "SELECT m.id, m.first_name, m.last_name, m.member_number,
                    COUNT(vps.id) AS matches_played,
                    SUM(vps.{$column}) AS total_value
             FROM volleyball_player_stats vps
             JOIN members m ON m.id = vps.member_id
             JOIN volleyball_matches vm ON vm.id = vps.match_id
             WHERE vm.club_id = ?
             GROUP BY m.id, m.first_name, m.last_name, m.member_number
             ORDER BY total_value DESC
             LIMIT ?"
        );
        $stmt->execute([$clubId, $limit]);
        return $stmt->fetchAll();
    }
}
