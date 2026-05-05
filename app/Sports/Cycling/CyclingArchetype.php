<?php

namespace App\Sports\Cycling;

use App\Sports\Support\TimingSport;

/**
 * Cycling — TimingSport archetype.
 *
 * Schema:
 *   cycling_results, cycling_athletes, cycling_ftp_tests
 */
class CyclingArchetype extends TimingSport
{
    public function key(): string
    {
        return 'cycling';
    }

    public function tables(): array
    {
        return ['cycling_results', 'cycling_athletes'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
