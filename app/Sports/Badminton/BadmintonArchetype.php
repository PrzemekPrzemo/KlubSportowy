<?php

namespace App\Sports\Badminton;

use App\Sports\Support\RacketSport;

/**
 * Badminton — RacketSport archetype.
 *
 * Schema: badminton_results (member_id, competition_name/_date, placement)
 */
class BadmintonArchetype extends RacketSport
{
    public function key(): string
    {
        return 'badminton';
    }

    public function tables(): array
    {
        return ['badminton_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
