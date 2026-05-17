<?php

namespace App\Sports\Powerlifting;

class PowerliftingModule
{
    public const KEY             = 'powerlifting';
    public const FEDERATION_CODE = 'PZPow';
    public const ARCHETYPE       = 'strength';
    public const LIFTS           = ['squat','bench','deadlift'];
    public const WEIGHT_CLASSES_MEN   = ['u59','u66','u74','u83','u93','u105','u120','o120'];
    public const WEIGHT_CLASSES_WOMEN = ['u47','u52','u57','u63','u69','u76','u84','o84'];

    public function metadata(): array
    {
        return [
            'key'                  => self::KEY,
            'federation'           => self::FEDERATION_CODE,
            'archetype'            => self::ARCHETYPE,
            'lifts'                => self::LIFTS,
            'weight_classes_men'   => self::WEIGHT_CLASSES_MEN,
            'weight_classes_women' => self::WEIGHT_CLASSES_WOMEN,
            'scoring'              => ['wilks','dots','ipf_gl'],
        ];
    }
}
