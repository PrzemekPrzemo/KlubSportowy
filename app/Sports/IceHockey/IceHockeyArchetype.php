<?php

namespace App\Sports\IceHockey;

use App\Sports\Support\TeamSport;

/**
 * Ice Hockey (PZHL) — TeamSport archetype.
 * Konwencja tabel z 001_icehockey.sql: icehockey_teams / matches / events.
 */
class IceHockeyArchetype extends TeamSport
{
    public function key(): string
    {
        return 'icehockey';
    }

    public function tables(): array
    {
        return ['icehockey_teams', 'icehockey_matches', 'icehockey_events'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
