<?php

namespace App\Sports\Athletics;

use App\Sports\Support\TimingSport;

/**
 * Athletics (PZLA) — TimingSport archetype.
 *
 * Schema:
 *   athletics_records, athletics_competitions, athletics_results
 *   (result_value DECIMAL REQUIRED, discipline_name VARCHAR REQUIRED,
 *    result_unit ENUM with default).
 */
class AthleticsArchetype extends TimingSport
{
    public function key(): string
    {
        return 'athletics';
    }

    public function tables(): array
    {
        return ['athletics_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
