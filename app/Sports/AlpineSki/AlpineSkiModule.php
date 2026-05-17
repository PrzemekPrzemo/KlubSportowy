<?php

namespace App\Sports\AlpineSki;

class AlpineSkiModule
{
    public const KEY             = 'alpineski';
    public const FEDERATION_CODE = 'PZN';
    public const ARCHETYPE       = 'timing';
    public const EVENTS          = ['slalom','giant_slalom','super_g','downhill','combined','parallel'];

    public function metadata(): array
    {
        return [
            'key'         => self::KEY,
            'federation'  => self::FEDERATION_CODE,
            'archetype'   => self::ARCHETYPE,
            'events'      => self::EVENTS,
            'fis_points'  => true,
        ];
    }
}
