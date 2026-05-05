<?php

namespace App\Sports\Karate;

use App\Sports\Support\CombatSport;

/**
 * Karate (PZK) — CombatSport archetype.
 *
 * Schema: karate_belts, karate_results
 */
class KarateArchetype extends CombatSport
{
    public function key(): string
    {
        return 'karate';
    }

    public function tables(): array
    {
        return ['karate_results', 'karate_belts'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
