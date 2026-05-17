<?php

namespace App\Sports\SkiJump;

class SkiJumpModule
{
    public const KEY             = 'skijump';
    public const FEDERATION_CODE = 'PZN';
    public const ARCHETYPE       = 'timing';
    public const HILL_SIZES      = ['NH','LH','FH'];
    public const SCORE_FORMULA   = 'distance_points + style_points + wind/gate_compensation';

    public function metadata(): array
    {
        return [
            'key'           => self::KEY,
            'federation'    => self::FEDERATION_CODE,
            'archetype'     => self::ARCHETYPE,
            'hill_sizes'    => self::HILL_SIZES,
            'score_formula' => self::SCORE_FORMULA,
            'fis_points'    => true,
        ];
    }
}
