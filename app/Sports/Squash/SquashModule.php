<?php

declare(strict_types=1);

namespace App\Sports\Squash;

use App\Sports\Support\SportModule;

/**
 * Squash — FULL RacketSport module.
 *
 * Cechy pełnej implementacji:
 *   - per-set PAR match stats (best-of-5, 11pkt point-a-rally)
 *   - lets / strokes tracking
 *   - historia + krajowe rankingi (squash_rankings + scorecard verify)
 *   - portal my_record
 */
final class SquashModule extends SportModule
{
    public function key(): string
    {
        return 'squash';
    }

    public function status(): string
    {
        return 'full';
    }

    public function fullFeatures(): array
    {
        return [
            'par_match_stats',
            'lets_strokes',
            'national_rankings',
            'match_history',
            'portal_my_record',
        ];
    }
}
