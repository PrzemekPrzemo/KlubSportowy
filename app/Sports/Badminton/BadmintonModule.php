<?php

declare(strict_types=1);

namespace App\Sports\Badminton;

use App\Sports\Support\SportModule;

/**
 * Badminton — FULL RacketSport module.
 *
 * Cechy pełnej implementacji:
 *   - per-set match stats (best-of-3, 21pkt, 2pkt przewagi)
 *   - singles + doubles + mixed (sport_badminton_member.discipline)
 *   - BWF points (uproszczony national ranking)
 *   - admin scorecard entry + verify, portal my_record
 */
final class BadmintonModule extends SportModule
{
    public function key(): string
    {
        return 'badminton';
    }

    public function status(): string
    {
        return 'full';
    }

    public function fullFeatures(): array
    {
        return [
            'match_set_stats',
            'doubles_team_tracking',
            'bwf_ranking_points',
            'portal_my_record',
            'admin_scorecard_verify',
        ];
    }
}
