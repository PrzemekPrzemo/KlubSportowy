<?php

namespace App\Sports\Bjj;

use App\Sports\Support\CombatSport;

/**
 * BJJ (Brazilian Jiu-Jitsu) — CombatSport archetype.
 *
 * Schema:
 *   bjj_results, bjj_belts
 */
class BjjArchetype extends CombatSport
{
    public function key(): string
    {
        return 'bjj';
    }

    public function tables(): array
    {
        return ['bjj_results', 'bjj_belts'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
