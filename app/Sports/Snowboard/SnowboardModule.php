<?php

namespace App\Sports\Snowboard;

class SnowboardModule
{
    public const KEY             = 'snowboard';
    public const FEDERATION_CODE = 'PZN';
    public const ARCHETYPE       = 'timing';
    public const EVENTS          = ['halfpipe','slopestyle','big_air','cross','parallel_gs','parallel_slalom'];

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
