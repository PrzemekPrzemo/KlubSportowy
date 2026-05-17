<?php

declare(strict_types=1);

namespace App\Sports\Squash\Models;

use App\Models\ClubScopedModel;

/**
 * sport_squash_match_stats — per-set PAR (best-of-5, 11pkt point-a-rally).
 * Dodatkowo: lets (przerwanie wymiany — powtorka) i strokes (kara dla rywala).
 */
class SquashMatchStatsModel extends ClubScopedModel
{
    protected string $table = 'sport_squash_match_stats';

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

    /** PAR 11pkt: max=11 z przewaga 2pkt (lub max=10 jesli set niedokonczony — false). */
    public static function isValidSetScore(int $home, int $away): bool
    {
        $max = max($home, $away);
        $min = min($home, $away);
        if ($max < 11) return false;
        return ($max - $min) >= 2;
    }
}
