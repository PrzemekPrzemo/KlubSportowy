<?php

namespace App\Sports\Judo;

use App\Sports\Support\CombatSport;

/**
 * Judo (PZJudo) — CombatSport archetype.
 *
 * Schema: judo_belts, judo_results
 */
class JudoArchetype extends CombatSport
{
    public function key(): string
    {
        return 'judo';
    }

    public function tables(): array
    {
        return ['judo_results', 'judo_belts'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
