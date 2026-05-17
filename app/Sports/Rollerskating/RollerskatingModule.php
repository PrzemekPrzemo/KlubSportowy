<?php

namespace App\Sports\Rollerskating;

class RollerskatingModule
{
    public const KEY             = 'rollerskating';
    public const FEDERATION_CODE = null;
    public const ARCHETYPE       = 'timing';
    public const EVENTS          = ['speed','figure','roller_derby','inline','aggressive'];
    public const DISTANCES       = [300, 500, 1000, 3000, 5000, 10000, 21100, 42200];

    public function metadata(): array
    {
        return [
            'key'         => self::KEY,
            'federation'  => self::FEDERATION_CODE,
            'archetype'   => self::ARCHETYPE,
            'events'      => self::EVENTS,
            'distances'   => self::DISTANCES,
        ];
    }
}
