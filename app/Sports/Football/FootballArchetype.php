<?php

namespace App\Sports\Football;

use App\Sports\Support\TeamSport;

/**
 * Football (PZPN) — TeamSport archetype.
 *
 * Schema:
 *   football_teams, football_matches, football_match_events
 */
class FootballArchetype extends TeamSport
{
    public function key(): string
    {
        return 'football';
    }

    public function tables(): array
    {
        return ['football_teams', 'football_matches', 'football_match_events'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
