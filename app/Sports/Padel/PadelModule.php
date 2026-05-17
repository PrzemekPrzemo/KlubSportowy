<?php

declare(strict_types=1);

namespace App\Sports\Padel;

use App\Sports\Support\SportModule;

/**
 * Padel — FULL doubles-only RacketSport module.
 *
 * Cechy pełnej implementacji:
 *   - pair tracking (sport_padel_pairs UNIQUE member_a/member_b)
 *   - pair ranking points
 *   - tennis-like scoring (set best-of-3, 6 gier + tie-break)
 *   - court reservations (już w 001_padel.sql) + admin pair management
 */
final class PadelModule extends SportModule
{
    public function key(): string
    {
        return 'padel';
    }

    public function status(): string
    {
        return 'full';
    }

    public function fullFeatures(): array
    {
        return [
            'pair_tracking',
            'pair_ranking',
            'doubles_scoring',
            'court_reservations',
            'admin_pair_management',
        ];
    }
}
