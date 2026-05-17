<?php

declare(strict_types=1);

namespace App\Sports\Badminton\Models;

use App\Models\ClubScopedModel;

/**
 * sport_badminton_match_stats — per-set wyniki meczu (best-of-3, 21pkt).
 */
class BadmintonMatchStatsModel extends ClubScopedModel
{
    protected string $table = 'sport_badminton_match_stats';

    /** Wszystkie sety dla danego meczu (uporzadkowane). */
    public function setsForMatch(int $matchId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE match_id = ? AND club_id = ?
           ORDER BY set_number ASC"
        );
        $stmt->execute([$matchId, $clubId]);
        return $stmt->fetchAll();
    }

    /** Zwraca true jesli set ma poprawne badmintonowe score (21-pkt, 2pkt advantage). */
    public static function isValidSetScore(int $home, int $away): bool
    {
        $max = max($home, $away);
        $min = min($home, $away);
        if ($max < 21 || $max > 30) return false;
        if ($max === 30) return $min >= 0; // cap
        return ($max - $min) >= 2;
    }
}
