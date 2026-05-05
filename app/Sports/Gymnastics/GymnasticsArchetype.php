<?php

namespace App\Sports\Gymnastics;

use App\Sports\Support\ScoringSport;

/**
 * Gymnastics — ScoringSport archetype.
 *
 * Schema:
 *   gymnastics_results (D-score, E-score, penalty, total GENERATED)
 *   gymnastics_minor_consents (RODO)
 */
class GymnasticsArchetype extends ScoringSport
{
    public function key(): string
    {
        return 'gymnastics';
    }

    public function tables(): array
    {
        return ['gymnastics_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
