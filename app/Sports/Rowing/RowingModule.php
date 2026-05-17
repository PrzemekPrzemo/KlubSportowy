<?php

namespace App\Sports\Rowing;

class RowingModule
{
    public const KEY             = 'rowing';
    public const FEDERATION_CODE = 'PZTW';
    public const ARCHETYPE       = 'timing';
    public const SPECIALTIES     = ['single','double','quadruple','eight'];
    public const BOAT_CLASSES    = ['1x','2x','2-','4x','4-','8+'];
    public const DISTANCES       = [500, 1000, 2000, 5000, 6000];

    public function metadata(): array
    {
        return [
            'key'          => self::KEY,
            'federation'   => self::FEDERATION_CODE,
            'archetype'    => self::ARCHETYPE,
            'specialties'  => self::SPECIALTIES,
            'boat_classes' => self::BOAT_CLASSES,
            'distances'    => self::DISTANCES,
        ];
    }
}
