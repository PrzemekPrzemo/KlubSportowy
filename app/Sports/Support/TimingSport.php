<?php

namespace App\Sports\Support;

/**
 * Archetyp indywidualne timing: zawodnik startuje w wyscigu, mierzymy czas
 * (i / lub dystans, splits, lap times). PB / SB / klubowe rekordy.
 *
 * Pasuje do: Athletics, Swimming, Cycling, Triathlon, Biathlon, Kayaking,
 *            Rowing, AlpineSki, XcSki, SkiJump, Snowboard.
 *
 * Konwencja tabel:
 *   <key>_athletes / <key>_swimmers / <key>_riders  — zawodnicy
 *   <key>_races / <key>_events / <key>_competitions  — wyscigi
 *   <key>_times / <key>_results / <key>_records      — czasy per zawodnik per wyscig
 */
abstract class TimingSport extends BaseSportArchetype
{
    public function entityTypes(): array
    {
        return ['athlete' => 'athletes', 'event' => 'races', 'result' => 'times'];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'athlete' => 8,
            'event'   => 4,    // 4 wyscigi/zawody
            'result'  => 16,   // 8 zawodnikow × 2 starty avg
        ];
    }

    public function tables(): array
    {
        $k = $this->key();
        return [
            "{$k}_athletes",
            "{$k}_races",
            "{$k}_times",
        ];
    }
}
