<?php

namespace App\Sports\DanceSport;

use App\Sports\Support\ScoringSport;

/**
 * DanceSport — ScoringSport archetype.
 *
 * Schema:
 *   dance_couples (partnerships), dance_results (uzywa leader_id zamiast member_id!)
 */
class DanceSportArchetype extends ScoringSport
{
    public function key(): string
    {
        return 'dance_sport';
    }

    public function tables(): array
    {
        return ['dance_results', 'dance_couples'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
