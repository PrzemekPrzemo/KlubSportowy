<?php

namespace App\Sports\Kickboxing;

use App\Sports\Support\CombatSport;

/**
 * Kickboxing — CombatSport archetype.
 *
 * Schema:
 *   kickboxing_results, kickboxing_belts
 */
class KickboxingArchetype extends CombatSport
{
    public function key(): string
    {
        return 'kickboxing';
    }

    public function tables(): array
    {
        return ['kickboxing_results', 'kickboxing_belts'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
