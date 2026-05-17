<?php

namespace App\Sports\Floorball;

/**
 * Metadata modulu Floorball (Unihokej, PZUnih / PFUF).
 * 5 + bramkarz, 3 x 20 min.
 */
class FloorballModule
{
    public const KEY              = 'floorball';
    public const FEDERATION_CODE  = 'PZUnih';
    public const POSITIONS        = ['bramkarz', 'obronca', 'napastnik', 'uniwersalny'];
    public const MATCH_FORMAT     = ['periods' => 3, 'duration_min' => 20];
    public const TEAM_SIZE        = 6;
    public const ROSTER_MAX       = 20;

    public function metadata(): array
    {
        return [
            'key'             => self::KEY,
            'name'            => 'Floorball (Unihokej)',
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
            '2-1-2' => ['bramkarz' => 1, 'obronca' => 2, 'napastnik' => 3],
            '2-2-1' => ['bramkarz' => 1, 'obronca' => 2, 'napastnik' => 3],
            '1-2-2' => ['bramkarz' => 1, 'obronca' => 1, 'napastnik' => 4],
        ];
    }
}
