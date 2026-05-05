<?php

namespace App\Sports\Support;

/**
 * Archetyp sila: zawodnik podnosi (snatch / clean&jerk / squat / bench /
 * deadlift), liczy sie najwyzszy zaliczony lift, formula scoringu (Sinclair
 * / Wilks) normalizuje wynik per kategoria wagowa.
 *
 * Pasuje do: Weightlifting, Powerlifting.
 *
 * Konwencja tabel:
 *   <key>_athletes  — zawodnicy z weight_class + best PR
 *   <key>_lifts     — pojedyncze podejscia (weight_kg, attempt_no, success)
 *   <key>_records   — rekordy klubu / kategorii (best PR per athlete per discipline)
 */
abstract class StrengthSport extends BaseSportArchetype
{
    public function entityTypes(): array
    {
        return ['athlete' => 'athletes', 'event' => 'competitions', 'result' => 'lifts'];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'athlete' => 5,
            'event'   => 2,
            'result'  => 30, // 5 zawodnikow × 2 kategorii × 3 podejscia
        ];
    }

    public function tables(): array
    {
        $k = $this->key();
        return [
            "{$k}_athletes",
            "{$k}_competitions",
            "{$k}_lifts",
        ];
    }
}
