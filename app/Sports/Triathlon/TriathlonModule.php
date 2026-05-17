<?php

namespace App\Sports\Triathlon;

class TriathlonModule
{
    public const KEY             = 'triathlon';
    public const FEDERATION_CODE = 'PZTri';
    public const ARCHETYPE       = 'timing';
    public const DISTANCES       = [
        'sprint'    => ['swim' => 750,  'bike' => 20000,  'run' => 5000],
        'olympic'   => ['swim' => 1500, 'bike' => 40000,  'run' => 10000],
        'half_im'   => ['swim' => 1900, 'bike' => 90000,  'run' => 21100],
        'ironman'   => ['swim' => 3800, 'bike' => 180000, 'run' => 42200],
    ];
    public const SPLITS = ['swim', 'bike', 'run', 't1', 't2'];

    public function metadata(): array
    {
        return [
            'key'         => self::KEY,
            'federation'  => self::FEDERATION_CODE,
            'archetype'   => self::ARCHETYPE,
            'distances'   => self::DISTANCES,
            'splits'      => self::SPLITS,
        ];
    }
}
