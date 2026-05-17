<?php

namespace App\Sports\Kayaking;

class KayakingModule
{
    public const KEY             = 'kayaking';
    public const FEDERATION_CODE = 'PZKaj';
    public const ARCHETYPE       = 'timing';
    public const BOAT_CLASSES    = ['K1','K2','K4','C1','C2','C4'];
    public const DISTANCES       = [200, 500, 1000, 5000, 10000];

    public function metadata(): array
    {
        return [
            'key'          => self::KEY,
            'federation'   => self::FEDERATION_CODE,
            'archetype'    => self::ARCHETYPE,
            'boat_classes' => self::BOAT_CLASSES,
            'distances'    => self::DISTANCES,
        ];
    }
}
