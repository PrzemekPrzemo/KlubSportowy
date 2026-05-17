<?php

namespace App\Sports\Curling;

use App\Sports\Support\TeamSport;

/**
 * Curling — TeamSport archetype.
 * Druzynowy 4-osobowy, 8-10 endow, scoring per end, hammer alternation.
 *
 * Konwencja tabel z 100_team_sports_stubs_full.sql:
 *   curling_teams / players / matches + sport_curling_match_ends
 *   (rinks/runs juz istnieja z winter_base).
 */
class CurlingArchetype extends TeamSport
{
    public function key(): string
    {
        return 'curling';
    }

    public function tables(): array
    {
        return ['curling_teams', 'curling_matches', 'sport_curling_match_ends'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
