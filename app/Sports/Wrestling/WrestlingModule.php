<?php

namespace App\Sports\Wrestling;

/**
 * Wrestling module — metadata + status modulu po promocji PARTIAL -> FULL.
 *
 * Funkcjonalnosc FULL:
 *   - Results + weight categories
 *   - Styles (freestyle / greco_roman / womens)
 *   - Technical match breakdown (takedowns, exposures, escapes, technical_fall, pin)
 *   - Member profile + ranking points
 */
class WrestlingModule
{
    public const KEY    = 'wrestling';
    public const STATUS = 'full';

    public const FEATURES = [
        'results',
        'weight_categories',
        'styles',
        'technical_breakdown',
        'member_profile',
        'rank_points',
    ];

    public static function key(): string    { return self::KEY; }
    public static function status(): string { return self::STATUS; }
    public static function features(): array { return self::FEATURES; }
}
