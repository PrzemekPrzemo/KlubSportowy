<?php

namespace App\Sports\Tennis;

use App\Sports\Support\RacketSport;

/**
 * Tennis — RacketSport archetype.
 *
 * Schema:
 *   tennis_matches (player1_id/player2_id, sets VARCHAR REQUIRED)
 *   tennis_rankings (member_id, season)
 *   tennis_courts (no member_id — pominiete przez seedera)
 */
class TennisArchetype extends RacketSport
{
    public function key(): string
    {
        return 'tennis';
    }

    public function tables(): array
    {
        return ['tennis_matches', 'tennis_rankings'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
