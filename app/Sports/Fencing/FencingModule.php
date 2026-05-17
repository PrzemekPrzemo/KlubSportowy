<?php

namespace App\Sports\Fencing;

/**
 * Fencing module — metadata + status modulu po promocji PARTIAL -> FULL.
 *
 * Funkcjonalnosc FULL:
 *   - Results
 *   - Multi-weapon (epee / foil / sabre) per zawodnik
 *   - FIE ranking
 *   - Pools + DE bracket
 *   - Touches scoring
 */
class FencingModule
{
    public const KEY    = 'fencing';
    public const STATUS = 'full';

    public const FEATURES = [
        'results',
        'fencers',
        'weapons',
        'multi_weapon',
        'fie_rank',
        'pools',
        'de_bracket',
        'touches_scoring',
    ];

    public static function key(): string    { return self::KEY; }
    public static function status(): string { return self::STATUS; }
    public static function features(): array { return self::FEATURES; }
}
