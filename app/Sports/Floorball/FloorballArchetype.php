<?php

namespace App\Sports\Floorball;

use App\Sports\Support\TeamSport;

/**
 * Floorball / Unihokej — TeamSport archetype.
 * Konwencja tabel z 001_floorball.sql: floorball_teams / matches / events.
 */
class FloorballArchetype extends TeamSport
{
    public function key(): string
    {
        return 'floorball';
    }

    public function tables(): array
    {
        return ['floorball_teams', 'floorball_matches', 'floorball_events'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
