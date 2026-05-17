<?php

declare(strict_types=1);

namespace App\Sports\Golf;

use App\Sports\Support\SportModule;

/**
 * Golf — FULL handicap-based sport module.
 *
 * Cechy pełnej implementacji:
 *   - scorecard tracking (18 dołków per round JSON)
 *   - polski handicap PZG (formula uproszczona WHS-like)
 *   - course database (sport_golf_courses + rating/slope)
 *   - admin verify scorecards + portal self-report
 */
final class GolfModule extends SportModule
{
    public function key(): string
    {
        return 'golf';
    }

    public function status(): string
    {
        return 'full';
    }

    public function fullFeatures(): array
    {
        return [
            'scorecard_tracking',
            'handicap_pzg',
            'course_database',
            'admin_verify',
            'portal_self_report',
        ];
    }
}
