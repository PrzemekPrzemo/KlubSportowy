<?php

namespace App\Sports\WaterPolo;

use App\Sports\Support\TeamSport;

/**
 * Pilka wodna — TeamSport archetype.
 * 7 v 7, 4 x 8 min, wykluczenie 20s (5 personal fouls = eliminacja).
 *
 * Konwencja tabel z 100_team_sports_stubs_full.sql:
 *   water_polo_teams / players / matches / events
 *   + sport_water_polo_match_stats
 */
class WaterPoloArchetype extends TeamSport
{
    public function key(): string
    {
        return 'water_polo';
    }

    public function tables(): array
    {
        return ['water_polo_teams', 'water_polo_matches', 'water_polo_events'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
