<?php

namespace App\Sports\Rugby;

/**
 * Metadata modulu Rugby (Union).
 * Scoring: try=5, conversion=2, penalty=3, drop goal=3.
 */
class RugbyModule
{
    public const KEY              = 'rugby';
    public const FEDERATION_CODE  = 'PZRugby';
    public const POSITIONS_FORWARDS = ['filar', 'hooker', 'młynarz', 'flanker', 'numer_8'];
    public const POSITIONS_BACKS    = ['łącznik_młyna', 'łącznik_ataku', 'środkowy', 'skrzydłowy', 'pełny'];
    public const POSITIONS          = [
        'filar', 'hooker', 'młynarz', 'flanker', 'numer_8',
        'łącznik_młyna', 'łącznik_ataku', 'środkowy', 'skrzydłowy', 'pełny',
        'uniwersalny',
    ];
    public const MATCH_FORMAT     = ['halves' => 2, 'duration_min' => 40];
    public const TEAM_SIZE        = 15;
    public const ROSTER_MAX       = 23;
    public const SCORING          = [
        'przyłożenie'   => 5,
        'podwyższenie'  => 2,
        'karny'         => 3,
        'drop'          => 3,
    ];

    public function metadata(): array
    {
        return [
            'key'              => self::KEY,
            'name'             => 'Rugby',
            'federation_code'  => self::FEDERATION_CODE,
            'positions'        => self::POSITIONS,
            'positions_groups' => [
                'forwards' => self::POSITIONS_FORWARDS,
                'backs'    => self::POSITIONS_BACKS,
            ],
            'match_format'     => self::MATCH_FORMAT,
            'team_size'        => self::TEAM_SIZE,
            'roster_max'       => self::ROSTER_MAX,
            'scoring'          => self::SCORING,
            'team_sport'       => true,
        ];
    }

    public function defaultFormations(): array
    {
        return [
            '15s standard' => ['forwards' => 8, 'backs' => 7],
            '7s'           => ['forwards' => 3, 'backs' => 4],
        ];
    }
}
