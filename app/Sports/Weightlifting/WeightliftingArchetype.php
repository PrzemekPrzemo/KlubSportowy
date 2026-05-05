<?php

namespace App\Sports\Weightlifting;

use App\Sports\Support\StrengthSport;

/**
 * Weightlifting (PZPC) — StrengthSport archetype.
 *
 * Schema:
 *   weightlifting_results (member_id, competition_*, weight_class REQUIRED,
 *     snatch_best, cleanjerk_best, total, sinclair_coeff)
 *   weightlifting_records (member_id, record_type ENUM, lift ENUM REQUIRED,
 *     weight_class REQUIRED, value_kg REQUIRED, set_at REQUIRED)
 */
class WeightliftingArchetype extends StrengthSport
{
    public function key(): string
    {
        return 'weightlifting';
    }

    public function tables(): array
    {
        return ['weightlifting_results', 'weightlifting_records'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
