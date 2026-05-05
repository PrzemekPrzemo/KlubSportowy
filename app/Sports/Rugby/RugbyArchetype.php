<?php

namespace App\Sports\Rugby;

use App\Sports\Support\TeamSport;

/**
 * Rugby (PZRugby) — TeamSport archetype.
 * Konwencja tabel z 001_rugby.sql: rugby_teams / matches / events.
 */
class RugbyArchetype extends TeamSport
{
    public function key(): string
    {
        return 'rugby';
    }

    public function tables(): array
    {
        return ['rugby_teams', 'rugby_matches', 'rugby_events'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
