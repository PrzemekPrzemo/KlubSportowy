<?php

namespace App\Sports\Rollerskating;

use App\Sports\Support\TimingSport;

/**
 * Rollerskating — TimingSport archetype.
 *
 * Schema:
 *   rollerskating_equipment, rollerskating_times
 *   (time_ms INT REQUIRED, record_date DATE REQUIRED — fallback do generic
 *    auto-fill bo TimingSportSeeder ma heurystyke dla *_ms i ogolnego DATE)
 */
class RollerskatingArchetype extends TimingSport
{
    public function key(): string
    {
        return 'rollerskating';
    }

    public function tables(): array
    {
        return ['rollerskating_times'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
