<?php

namespace App\Sports\Golf;

use App\Sports\Support\RacketSport;

/**
 * Golf — RacketSport archetype (cel + handicap-based).
 *
 * Schema:
 *   golf_handicaps (member_id, whs_index REQUIRED, updated_at DATE REQUIRED)
 *   golf_rounds (member_id, course_name REQUIRED, round_date REQUIRED, tees ENUM)
 */
class GolfArchetype extends RacketSport
{
    public function key(): string
    {
        return 'golf';
    }

    public function tables(): array
    {
        return ['golf_rounds', 'golf_handicaps'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
