<?php

namespace App\Sports\Futsal;

/**
 * Metadata modulu Futsal — uzywane przez UI i konfiguracje.
 * Wzorzec drozynowy: 4 + bramkarz, 2 polowy x 20 min.
 */
class FutsalModule
{
    public const KEY              = 'futsal';
    public const FEDERATION_CODE  = 'PZPN';
    public const POSITIONS        = ['bramkarz', 'obronca', 'skrzydlowy', 'pivot', 'uniwersalny'];
    public const MATCH_FORMAT     = ['halves' => 2, 'duration_min' => 20];
    public const TEAM_SIZE        = 5;
    public const ROSTER_MAX       = 14;

    public function metadata(): array
    {
        return [
            'key'              => self::KEY,
            'name'             => 'Futsal',
            'federation_code'  => self::FEDERATION_CODE,
            'positions'        => self::POSITIONS,
            'match_format'     => self::MATCH_FORMAT,
            'team_size'        => self::TEAM_SIZE,
            'roster_max'       => self::ROSTER_MAX,
            'team_sport'       => true,
        ];
    }

    /** Domyslne ustawienia taktyczne — wykorzystywane w widokach formacji. */
    public function defaultFormations(): array
    {
        return [
            '1-2-1' => ['bramkarz' => 1, 'obronca' => 1, 'skrzydlowy' => 2, 'pivot' => 1],
            '2-2'   => ['bramkarz' => 1, 'obronca' => 2, 'skrzydlowy' => 2, 'pivot' => 0],
            '3-1'   => ['bramkarz' => 1, 'obronca' => 3, 'skrzydlowy' => 0, 'pivot' => 1],
            '4-0'   => ['bramkarz' => 1, 'obronca' => 0, 'skrzydlowy' => 4, 'pivot' => 0],
        ];
    }
}
