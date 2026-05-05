<?php

namespace App\Sports\Support;

/**
 * Archetyp scoring / sedziowanie: zawodnik wykonuje uklad / przyrzad / dressage,
 * sedziowie wystawiaja punkty (technique/execution/presentation), suma daje
 * wynik. Brak directowego head-to-head.
 *
 * Pasuje do: FigureSkating, Gymnastics, DanceSport, Equestrian.
 *
 * Konwencja tabel:
 *   <key>_athletes      — zawodnicy
 *   <key>_performances  — wystepy/elementy
 *   <key>_judge_scores  — punkty od sedziow (TES/PCS dla łyzwiarstwa, D/E dla gimnastyki)
 */
abstract class ScoringSport extends BaseSportArchetype
{
    public function entityTypes(): array
    {
        return ['athlete' => 'athletes', 'event' => 'performances', 'result' => 'judge_scores'];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'athlete' => 6,
            'event'   => 3,   // 3 wystepy w sezonie
            'result'  => 9,   // 3 sedziow × 3 wystepy
        ];
    }

    public function tables(): array
    {
        $k = $this->key();
        return [
            "{$k}_athletes",
            "{$k}_performances",
            "{$k}_judge_scores",
        ];
    }
}
