<?php

namespace App\Sports\Powerlifting;

use App\Sports\Support\StrengthSport;

/**
 * Powerlifting (PZTSS) — StrengthSport archetype.
 *
 * Schema:
 *   powerlifting_results (member_id, competition_*, weight_class REQUIRED,
 *     squat_best, bench_best, deadlift_best, total, wilks_coeff,
 *     federation_type ENUM with default)
 *   powerlifting_records (member_id, lift_type ENUM REQUIRED,
 *     weight_kg REQUIRED, set_date REQUIRED)
 */
class PowerliftingArchetype extends StrengthSport
{
    public function key(): string
    {
        return 'powerlifting';
    }

    public function tables(): array
    {
        return ['powerlifting_results', 'powerlifting_records'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
