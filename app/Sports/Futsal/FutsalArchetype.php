<?php

namespace App\Sports\Futsal;

use App\Sports\Support\TeamSport;

/**
 * Futsal (PZPN) — TeamSport archetype.
 * Halowa pilka nozna 5-osobowa (4 + bramkarz), 2 polowy x 20 min.
 *
 * Konwencja tabel z 001_futsal.sql:
 *   futsal_teams / futsal_players / futsal_matches / futsal_events
 *   + sport_futsal_match_stats (agregaty per-team)
 */
class FutsalArchetype extends TeamSport
{
    public function key(): string
    {
        return 'futsal';
    }

    public function tables(): array
    {
        return ['futsal_teams', 'futsal_matches', 'futsal_events'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
