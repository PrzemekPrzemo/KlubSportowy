<?php

namespace App\Sports\Mma;

/**
 * MMA module — metadata + status modulu po promocji PARTIAL -> FULL.
 *
 * Funkcjonalnosc FULL:
 *   - Fight record (W/L/D/KO/Submission/Decision)
 *   - Discipline mix (% striking / wrestling / grappling)
 *   - Weight classes
 *   - Fighters profile (stance / weight class)
 */
class MmaModule
{
    public const KEY    = 'mma';
    public const STATUS = 'full';

    public const FEATURES = [
        'fighters',
        'results',
        'methods',
        'weight_classes',
        'fight_record',
        'discipline_mix',
        'amateur_pro',
    ];

    public static function key(): string    { return self::KEY; }
    public static function status(): string { return self::STATUS; }
    public static function features(): array { return self::FEATURES; }
}
