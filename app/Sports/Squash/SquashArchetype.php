<?php

namespace App\Sports\Squash;

use App\Sports\Support\RacketSport;

/**
 * Squash — RacketSport archetype.
 *
 * Schema:
 *   squash_results (member_id, match_date REQUIRED, category ENUM, sets_won/lost)
 *   squash_rankings (member_id, season)
 */
class SquashArchetype extends RacketSport
{
    public function key(): string
    {
        return 'squash';
    }

    public function tables(): array
    {
        return ['squash_results', 'squash_rankings'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
