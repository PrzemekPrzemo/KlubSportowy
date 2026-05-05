<?php

namespace App\Sports\Fencing;

use App\Sports\Support\CombatSport;

/**
 * Fencing — CombatSport archetype.
 *
 * Schema:
 *   fencing_results, fencing_fencers (per-sport entity)
 *
 * Uwaga: Fencing uzywa `fencing_fencers` (nie standardowego _fighters),
 * wiec generic CombatSportSeeder zaseeduje tylko results — fencers
 * pozostaje opcjonalnie do osobnego seedera w przyszlosci.
 */
class FencingArchetype extends CombatSport
{
    public function key(): string
    {
        return 'fencing';
    }

    public function tables(): array
    {
        return ['fencing_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
