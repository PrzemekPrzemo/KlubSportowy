<?php

namespace App\Sports\Curling\Models;

use App\Models\ClubScopedModel;
use App\Sports\Curling\CurlingModule;

/**
 * Model per-end scoringu w curlingu (sport_curling_match_ends).
 * Trzyma score per end + ktora strona miala hammer.
 */
class CurlingMatchEndModel extends ClubScopedModel
{
    protected string $table = 'sport_curling_match_ends';

    public function listForMatch(int $matchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_curling_match_ends
             WHERE match_id = ? AND club_id <=> ?
             ORDER BY end_number ASC"
        );
        $stmt->execute([$matchId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    /** Dodaj/aktualizuj wynik dla danego endu. */
    public function upsertEnd(int $matchId, int $endNumber, int $homeScore, int $awayScore, string $hammerSide): void
    {
        $hammerSide = $hammerSide === 'away' ? 'away' : 'home';
        $clubId     = $this->clubId();
        $homeScore  = max(0, $homeScore);
        $awayScore  = max(0, $awayScore);

        $this->db->prepare(
            "INSERT INTO sport_curling_match_ends
                 (match_id, club_id, end_number, home_score, away_score, hammer_side)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 home_score=VALUES(home_score),
                 away_score=VALUES(away_score),
                 hammer_side=VALUES(hammer_side)"
        )->execute([$matchId, $clubId, $endNumber, $homeScore, $awayScore, $hammerSide]);
    }

    /** Suma punktow do tej pory (h, a). */
    public function totals(int $matchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(home_score),0) AS h, COALESCE(SUM(away_score),0) AS a
             FROM sport_curling_match_ends WHERE match_id=? AND club_id <=> ?"
        );
        $stmt->execute([$matchId, $this->clubId()]);
        $row = $stmt->fetch() ?: ['h' => 0, 'a' => 0];
        return ['home' => (int)$row['h'], 'away' => (int)$row['a']];
    }

    /** Wylicz hammer dla nastepnego endu. */
    public function nextHammer(string $previous, int $homeInEnd, int $awayInEnd): string
    {
        $module = new CurlingModule();
        return $module->nextHammer($previous, $homeInEnd, $awayInEnd);
    }
}
