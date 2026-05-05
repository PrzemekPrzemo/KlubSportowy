<?php

namespace App\Sports\Sambo;

use App\Sports\Support\CombatSport;

/**
 * Sambo — CombatSport archetype.
 *
 * Schema:
 *   sambo_results, sambo_belts
 */
class SamboArchetype extends CombatSport
{
    public function key(): string
    {
        return 'sambo';
    }

    public function tables(): array
    {
        return ['sambo_results', 'sambo_belts'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
