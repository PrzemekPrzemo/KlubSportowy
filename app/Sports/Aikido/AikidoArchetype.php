<?php

namespace App\Sports\Aikido;

use App\Sports\Support\CombatSport;

/**
 * Aikido — CombatSport archetype.
 *
 * Schema:
 *   aikido_results, aikido_belts
 */
class AikidoArchetype extends CombatSport
{
    public function key(): string
    {
        return 'aikido';
    }

    public function tables(): array
    {
        return ['aikido_results', 'aikido_belts'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
