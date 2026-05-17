<?php

namespace App\Sports\Boxing;

/**
 * Boxing module — metadata + status modulu po promocji PARTIAL -> FULL.
 *
 * Funkcjonalnosc FULL:
 *   - W-L-D record (kartoteka)
 *   - License levels (junior/senior/elite/professional)
 *   - Weight class history (track zmian wagi w czasie)
 *   - Medical exams (badania lekarskie)
 *   - Stance / reach
 */
class BoxingModule
{
    public const KEY    = 'boxing';
    public const STATUS = 'full';

    /** @var string[] features FULL */
    public const FEATURES = [
        'results',
        'medicals',
        'weight_classes',
        'fight_record',
        'license_levels',
        'weight_history',
        'amateur_pro',
    ];

    public static function key(): string    { return self::KEY; }
    public static function status(): string { return self::STATUS; }
    public static function features(): array { return self::FEATURES; }
}
