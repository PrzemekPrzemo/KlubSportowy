<?php

namespace App\Sports\Swimming;

/**
 * Metadata modułu Swimming (TimingSport archetype).
 * Część PROMOTION timing-sports → FULL. Używany przez nową, wspólną
 * tabelę `sport_timing_results` (sport_key='swimming') jako źródło
 * dropdownów specialty + dystansów dla UI/portal.
 */
class SwimmingModule
{
    public const KEY             = 'swimming';
    public const FEDERATION_CODE = 'PZP';
    public const ARCHETYPE       = 'timing';
    public const SPECIALTIES     = ['freestyle','backstroke','breaststroke','butterfly','medley'];
    public const DISTANCES       = [25, 50, 100, 200, 400, 800, 1500];

    public function metadata(): array
    {
        return [
            'key'         => self::KEY,
            'federation'  => self::FEDERATION_CODE,
            'archetype'   => self::ARCHETYPE,
            'specialties' => self::SPECIALTIES,
            'distances'   => self::DISTANCES,
            'time_unit'   => 'ms',
            'env'         => 'pool/openwater',
        ];
    }
}
