<?php

namespace App\Sports\XcSki;

class XcSkiModule
{
    public const KEY             = 'xcski';
    public const FEDERATION_CODE = 'PZN';
    public const ARCHETYPE       = 'timing';
    public const EVENTS          = ['classic','freestyle','skiathlon','sprint','team_sprint','relay'];
    public const DISTANCES       = [1500, 5000, 10000, 15000, 30000, 50000];

    public function metadata(): array
    {
        return [
            'key'         => self::KEY,
            'federation'  => self::FEDERATION_CODE,
            'archetype'   => self::ARCHETYPE,
            'events'      => self::EVENTS,
            'distances'   => self::DISTANCES,
            'fis_points'  => true,
        ];
    }
}
