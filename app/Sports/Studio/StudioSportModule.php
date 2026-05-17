<?php

namespace App\Sports\Studio;

/**
 * Bazowa klasa dla sportow studio/cardio (yoga, fitness, pilates).
 *
 * Wszystkie 3 sporty dziela ten sam model biznesowy:
 *   - klasy grupowe wg tygodniowego harmonogramu
 *   - karnety (single / multi_class / unlimited_period)
 *   - zapis na zajecia (book) + zuzycie pass'a
 *   - check-in przez instruktora (mark attended)
 *
 * Konkretne moduly (YogaModule / FitnessModule / PilatesModule) tylko
 * podaja metadata i default-y (lista przykladowych klas + 4 typy karnetow).
 *
 * Dane sa w tabelach `studio_*` z dyskryminatorem `sport_key`.
 */
abstract class StudioSportModule
{
    /** Klucz sportu — yoga | fitness | pilates */
    abstract public function key(): string;

    /** Nazwa wyswietlana (PL) */
    abstract public function name(): string;

    /**
     * Domyslne klasy (templates) seedowane przy onboardingu klubu.
     * Kazdy element: [name, difficulty, duration_min, day_of_week, time_start, max_capacity]
     */
    abstract public function defaultClassTemplates(): array;

    /**
     * Domyslne 4 typy karnetow:
     *   single (1 zajecia), 4-pack, 8-pack, monthly_unlimited.
     * Kazdy element: [code, name, type, classes_count, validity_days, price_cents]
     */
    public function defaultPassTypes(): array
    {
        $sport = $this->key();
        return [
            [
                'code'          => $sport . '_single',
                'name'          => $this->name() . ' — wejscie 1x',
                'type'          => 'single',
                'classes_count' => 1,
                'validity_days' => 7,
                'price_cents'   => 4000,
            ],
            [
                'code'          => $sport . '_4pack',
                'name'          => $this->name() . ' — karnet 4 wejscia',
                'type'          => 'multi_class',
                'classes_count' => 4,
                'validity_days' => 30,
                'price_cents'   => 14000,
            ],
            [
                'code'          => $sport . '_8pack',
                'name'          => $this->name() . ' — karnet 8 wejsc',
                'type'          => 'multi_class',
                'classes_count' => 8,
                'validity_days' => 45,
                'price_cents'   => 25000,
            ],
            [
                'code'          => $sport . '_unlimited_month',
                'name'          => $this->name() . ' — open / miesiac',
                'type'          => 'unlimited_period',
                'classes_count' => null,
                'validity_days' => 30,
                'price_cents'   => 35000,
            ],
        ];
    }

    /** Metadata modulu — uzywane przez SportModuleLoader / UI. */
    public function metadata(): array
    {
        return [
            'key'         => $this->key(),
            'name'        => $this->name(),
            'family'      => 'studio',
            'has_classes' => true,
            'has_passes'  => true,
            'has_checkin' => true,
        ];
    }
}
