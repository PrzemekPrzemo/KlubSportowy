<?php

declare(strict_types=1);

namespace App\Sports\Archery;

use App\Sports\Support\SportModule;

/**
 * Archery — FULL precision-sport module.
 *
 * Cechy pełnej implementacji:
 *   - scorecard tracking per dystans (18m / 70m / 90m) + ends (3-6 strzał)
 *   - totals 10s + Xs (inner-tens)
 *   - bow type: recurve / compound / barebow / longbow
 *   - portal self-report + admin verify
 */
final class ArcheryModule extends SportModule
{
    public function key(): string
    {
        return 'archery';
    }

    public function status(): string
    {
        return 'full';
    }

    public function fullFeatures(): array
    {
        return [
            'scorecard_per_round',
            'distance_categories',
            'bow_type_tracking',
            'tens_xs_counters',
            'portal_self_report',
            'admin_verify',
        ];
    }
}
