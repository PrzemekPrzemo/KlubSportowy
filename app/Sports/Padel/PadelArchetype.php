<?php

namespace App\Sports\Padel;

use App\Sports\Support\RacketSport;

/**
 * Padel — RacketSport archetype.
 *
 * Schema (no traditional _results table — Padel uzywa par i rezerwacji):
 *   padel_courts (no member_id)
 *   padel_pairs (player1_id/player2_id, ranking_points)
 *   padel_reservations (member_id)
 *
 * Seeder zaseeduje padel_pairs (pary) i padel_reservations (rezerwacje
 * kortow) ktore sa kluczowe dla demo Padelowego klubu.
 */
class PadelArchetype extends RacketSport
{
    public function key(): string
    {
        return 'padel';
    }

    public function tables(): array
    {
        return ['padel_pairs'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
