<?php

namespace App\Sports\Triathlon;

use App\Sports\Support\TimingSport;

/**
 * Triathlon — TimingSport archetype.
 *
 * Schema:
 *   triathlon_athletes, triathlon_results
 */
class TriathlonArchetype extends TimingSport
{
    public function key(): string
    {
        return 'triathlon';
    }

    public function tables(): array
    {
        return ['triathlon_results', 'triathlon_athletes'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
