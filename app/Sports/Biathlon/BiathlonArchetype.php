<?php

namespace App\Sports\Biathlon;

use App\Sports\Support\TimingSport;

/**
 * Biathlon — TimingSport archetype.
 *
 * Schema:
 *   biathlon_results (format ENUM default, distance_km REQUIRED)
 */
class BiathlonArchetype extends TimingSport
{
    public function key(): string
    {
        return 'biathlon';
    }

    public function tables(): array
    {
        return ['biathlon_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
