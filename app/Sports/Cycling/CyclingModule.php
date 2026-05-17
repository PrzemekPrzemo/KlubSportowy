<?php

namespace App\Sports\Cycling;

class CyclingModule
{
    public const KEY             = 'cycling';
    public const FEDERATION_CODE = 'PZKol';
    public const ARCHETYPE       = 'timing';
    public const SPECIALTIES     = ['road','mtb','track','cyclocross','bmx','gravel'];
    public const DISTANCES       = [10000, 20000, 40000, 80000, 100000, 160000, 200000];

    public function metadata(): array
    {
        return [
            'key'          => self::KEY,
            'federation'   => self::FEDERATION_CODE,
            'archetype'    => self::ARCHETYPE,
            'specialties'  => self::SPECIALTIES,
            'distances'    => self::DISTANCES,
            'uci_supported' => true,
            'power_watts'   => true,
        ];
    }
}
