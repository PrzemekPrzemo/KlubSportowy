<?php

namespace App\Sports\Handball;

use App\Sports\Support\TeamSport;

/**
 * Handball — TeamSport archetype.
 *
 * Konwencja tabel zgodna z migracja 001_handball.sql:
 *   handball_teams, handball_players, handball_matches, handball_events
 *
 * Override TeamSport.tables() bo Handball uzywa _events zamiast
 * domyslnego _match_events.
 */
class HandballArchetype extends TeamSport
{
    public function key(): string
    {
        return 'handball';
    }

    public function tables(): array
    {
        return [
            'handball_teams',
            'handball_matches',
            'handball_events',
        ];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
