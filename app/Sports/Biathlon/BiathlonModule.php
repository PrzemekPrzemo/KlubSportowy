<?php

namespace App\Sports\Biathlon;

class BiathlonModule
{
    public const KEY             = 'biathlon';
    public const FEDERATION_CODE = 'PZBiath';
    public const ARCHETYPE       = 'timing';
    public const EVENTS          = ['sprint','individual','pursuit','mass_start','relay'];
    public const SHOOTING_PENALTY_SECONDS = 60;
    public const SHOOTING_PENALTY_LOOP_M  = 150;

    public function metadata(): array
    {
        return [
            'key'          => self::KEY,
            'federation'   => self::FEDERATION_CODE,
            'archetype'    => self::ARCHETYPE,
            'events'       => self::EVENTS,
            'shooting'     => ['penalty_seconds' => self::SHOOTING_PENALTY_SECONDS, 'loop_m' => self::SHOOTING_PENALTY_LOOP_M],
            'distances'    => [7500, 10000, 12500, 15000, 20000],
        ];
    }
}
