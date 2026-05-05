<?php

namespace App\Sports\Volleyball;

use App\Sports\Support\TeamSport;

/**
 * Volleyball (PZPS) — TeamSport archetype.
 *
 * Schema: volleyball_teams, volleyball_matches
 */
class VolleyballArchetype extends TeamSport
{
    public function key(): string
    {
        return 'volleyball';
    }

    public function tables(): array
    {
        return ['volleyball_teams', 'volleyball_matches'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
