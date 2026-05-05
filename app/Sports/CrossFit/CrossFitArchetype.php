<?php

namespace App\Sports\CrossFit;

use App\Sports\Support\NicheSport;

/**
 * CrossFit — NicheSport archetype.
 *
 * Schema dependencies:
 *   crossfit_wods  (parent — name + wod_type, no member)
 *   crossfit_scores (child — wod_id REQUIRED FK + member_id + score)
 *   crossfit_prs   (member_id + movement + pr_value + pr_date)
 *
 * Tabele MUSZA byc w kolejnosci: wods → scores → prs.
 */
class CrossFitArchetype extends NicheSport
{
    public function key(): string
    {
        return 'crossfit';
    }

    public function tables(): array
    {
        return ['crossfit_wods', 'crossfit_scores', 'crossfit_prs'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
