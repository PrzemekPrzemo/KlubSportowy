<?php

namespace App\Sports\FieldHockey;

use App\Sports\Support\TeamSport;

/**
 * Hokej na trawie (PZHnT) — TeamSport archetype.
 * Konwencja tabel z 001 migracji: field_hockey_teams / matches / events
 * (uwaga: tabele uzywaja UNDERSCORE w 'field_hockey', a klucz pluginu
 * 'fieldhockey' bez podkreslenia).
 */
class FieldHockeyArchetype extends TeamSport
{
    public function key(): string
    {
        return 'fieldhockey';
    }

    public function tables(): array
    {
        return ['field_hockey_teams', 'field_hockey_matches', 'field_hockey_events'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
