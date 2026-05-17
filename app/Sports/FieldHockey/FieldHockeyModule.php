<?php

namespace App\Sports\FieldHockey;

/**
 * Metadata modulu Hokej na trawie (PZHnT/PZHT).
 */
class FieldHockeyModule
{
    public const KEY              = 'fieldhockey';
    public const FEDERATION_CODE  = 'PZHT';
    public const POSITIONS        = ['bramkarz', 'obronca', 'pomocnik', 'atakujacy', 'uniwersalny'];
    public const MATCH_FORMAT     = ['quarters' => 4, 'duration_min' => 15];
    public const TEAM_SIZE        = 11;
    public const ROSTER_MAX       = 16;

    public function metadata(): array
    {
        return [
            'key'             => self::KEY,
            'name'            => 'Hokej na trawie',
            'federation_code' => self::FEDERATION_CODE,
            'positions'       => self::POSITIONS,
            'match_format'    => self::MATCH_FORMAT,
            'team_size'       => self::TEAM_SIZE,
            'roster_max'      => self::ROSTER_MAX,
            'team_sport'      => true,
        ];
    }

    public function defaultFormations(): array
    {
        return [
            '4-3-3' => ['bramkarz' => 1, 'obronca' => 4, 'pomocnik' => 3, 'atakujacy' => 3],
            '5-3-2' => ['bramkarz' => 1, 'obronca' => 5, 'pomocnik' => 3, 'atakujacy' => 2],
            '3-4-3' => ['bramkarz' => 1, 'obronca' => 3, 'pomocnik' => 4, 'atakujacy' => 3],
        ];
    }
}
