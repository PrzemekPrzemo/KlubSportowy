<?php

namespace App\Sports\WaterPolo;

/**
 * Metadata modulu Water Polo.
 * Sport druzynowy w basenie: 7 v 7, 4 x 8 min,
 * 5 personal fouls = wykluczenie z meczu.
 */
class WaterPoloModule
{
    public const KEY              = 'water_polo';
    public const FEDERATION_CODE  = null; // brak adaptera w MVP
    public const POSITIONS        = ['bramkarz', 'obronca', 'skrzydlowy', 'center_forward', 'driver', 'uniwersalny'];
    public const MATCH_FORMAT     = ['quarters' => 4, 'duration_min' => 8];
    public const TEAM_SIZE        = 7;
    public const ROSTER_MAX       = 13;
    public const EXCLUSION_SECONDS = 20;
    public const MAX_PERSONAL_FOULS = 5;

    public function metadata(): array
    {
        return [
            'key'                => self::KEY,
            'name'               => 'Pilka wodna',
            'federation_code'    => self::FEDERATION_CODE,
            'positions'          => self::POSITIONS,
            'match_format'       => self::MATCH_FORMAT,
            'team_size'          => self::TEAM_SIZE,
            'roster_max'         => self::ROSTER_MAX,
            'exclusion_seconds'  => self::EXCLUSION_SECONDS,
            'max_personal_fouls' => self::MAX_PERSONAL_FOULS,
            'team_sport'         => true,
        ];
    }

    public function defaultFormations(): array
    {
        // Klasyczna formacja basenowa: 1 GK + 3-3 (lub umbrella 4-2)
        return [
            '3-3 (klasyk)' => ['bramkarz' => 1, 'obronca' => 3, 'driver' => 3],
            '4-2 (umbrella)' => ['bramkarz' => 1, 'obronca' => 4, 'center_forward' => 2],
        ];
    }
}
