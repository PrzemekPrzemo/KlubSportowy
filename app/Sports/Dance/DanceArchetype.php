<?php

namespace App\Sports\Dance;

use App\Sports\Support\ScoringSport;

/**
 * DANCE — ScoringSport (judges-based scoring: technique, artistry, difficulty).
 *
 * Schema:
 *   sport_dance_styles         — katalog stylow (globalne + klubowe)
 *   sport_dance_member_styles  — przypisanie stylow zawodnikom (z poziomem + partnerem)
 *   sport_dance_performances   — wystepy w turniejach
 *   sport_dance_judge_scores   — oceny sedziow per wystep
 */
class DanceArchetype extends ScoringSport
{
    public function key(): string
    {
        return 'dance';
    }

    public function tables(): array
    {
        return [
            'sport_dance_styles',
            'sport_dance_member_styles',
            'sport_dance_performances',
            'sport_dance_judge_scores',
            // legacy z 010_grace_base.sql:
            'dance_routines',
            'dance_performances',
        ];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
