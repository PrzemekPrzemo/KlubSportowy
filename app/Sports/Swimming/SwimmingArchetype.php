<?php

namespace App\Sports\Swimming;

use App\Sports\Support\TimingSport;

/**
 * Swimming — TimingSport archetype.
 *
 * Schema: swimming_results (stroke ENUM REQUIRED, distance_m, time_ms,
 * pool_type ENUM with default, score_date).
 */
class SwimmingArchetype extends TimingSport
{
    public function key(): string
    {
        return 'swimming';
    }

    public function tables(): array
    {
        return ['swimming_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
