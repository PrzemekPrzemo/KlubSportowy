<?php

namespace App\Sports\Taekwondo;

use App\Sports\Support\CombatSport;

/**
 * Taekwondo (PZT-KD) — CombatSport archetype.
 *
 * Schema: taekwondo_results, taekwondo_belts
 */
class TaekwondoArchetype extends CombatSport
{
    public function key(): string
    {
        return 'taekwondo';
    }

    public function tables(): array
    {
        return ['taekwondo_results', 'taekwondo_belts'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
