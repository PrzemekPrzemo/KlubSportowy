<?php

namespace App\Sports\Mma;

use App\Sports\Support\CombatSport;

/**
 * MMA — CombatSport archetype.
 *
 * Schema:
 *   mma_results, mma_fighters
 */
class MmaArchetype extends CombatSport
{
    public function key(): string
    {
        return 'mma';
    }

    public function tables(): array
    {
        return ['mma_results', 'mma_fighters'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
