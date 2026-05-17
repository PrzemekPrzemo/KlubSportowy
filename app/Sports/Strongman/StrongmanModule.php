<?php

namespace App\Sports\Strongman;

class StrongmanModule
{
    public const KEY             = 'strongman';
    public const FEDERATION_CODE = null;
    public const ARCHETYPE       = 'strength';
    public const EVENTS          = [
        'deadlift',
        'log_press',
        'yoke',
        'atlas_stones',
        'farmers_walk',
        'tire_flip',
        'truck_pull',
        'circus_dumbbell',
    ];
    public const WEIGHT_CLASSES  = ['u90','u105','u125','o125'];

    public function metadata(): array
    {
        return [
            'key'            => self::KEY,
            'federation'     => self::FEDERATION_CODE,
            'archetype'      => self::ARCHETYPE,
            'events'         => self::EVENTS,
            'weight_classes' => self::WEIGHT_CLASSES,
        ];
    }
}
