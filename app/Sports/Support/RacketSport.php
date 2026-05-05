<?php

namespace App\Sports\Support;

/**
 * Archetyp rakieta / cel: zawodnik vs zawodnik (lub debel), best_of_3/5
 * setow do 11/15/21/25 punktow. Tournaments z drabinka.
 *
 * Pasuje do: Tennis, TableTennis, Badminton, Squash, Archery (cel),
 *            Golf (handicap-based).
 *
 * Konwencja tabel:
 *   <key>_players       — zawodnicy
 *   <key>_matches       — pojedynki (player1_id, player2_id, winner_id)
 *   <key>_match_sets    — sety/rundy ze score per zawodnik
 *   <key>_tournaments   — turnieje z drabinka
 */
abstract class RacketSport extends BaseSportArchetype
{
    public function entityTypes(): array
    {
        return ['athlete' => 'players', 'event' => 'matches', 'result' => 'match_sets'];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'athlete' => 8,
            'event'   => 6,   // 6 meczow round-robin
            'result'  => 18,  // 6 meczow × 3 sety avg
        ];
    }

    public function tables(): array
    {
        $k = $this->key();
        return [
            "{$k}_players",
            "{$k}_matches",
            "{$k}_match_sets",
        ];
    }
}
