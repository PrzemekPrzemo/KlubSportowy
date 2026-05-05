<?php

namespace App\Sports\Boxing;

use App\Sports\Support\CombatSport;

/**
 * Boxing — CombatSport archetype.
 *
 * Schema (migrations 001-003):
 *   boxing_results, boxing_medicals
 *
 * Override tables() bo Boxing nie ma _fighters/_fights — tylko results.
 * Demo seeder (CombatSportSeeder) wykryje przez introspekcje brak
 * opcjonalnych tabel i zaseeduje tylko results.
 */
class BoxingArchetype extends CombatSport
{
    public function key(): string
    {
        return 'boxing';
    }

    public function tables(): array
    {
        return ['boxing_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
