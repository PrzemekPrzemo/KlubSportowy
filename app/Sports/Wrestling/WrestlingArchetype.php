<?php

namespace App\Sports\Wrestling;

use App\Sports\Support\CombatSport;

/**
 * Wrestling — CombatSport archetype.
 *
 * Schema:
 *   wrestling_results
 */
class WrestlingArchetype extends CombatSport
{
    public function key(): string
    {
        return 'wrestling';
    }

    public function tables(): array
    {
        return ['wrestling_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
