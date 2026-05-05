<?php

namespace App\Sports\Support;

/**
 * Archetyp walki / sztuki walki: zawodnik ma stopien (kyu/dan/pas/weight class),
 * walki/pojedynki maja result W/L/D/NC i metode (KO/TKO/decyzja/SUB).
 *
 * Pasuje do: Boxing, Kickboxing, MMA, Wrestling, Sambo, Aikido, Bjj,
 *            Judo, Karate, Taekwondo, Fencing.
 *
 * Konwencja tabel:
 *   <key>_fighters / <key>_athletes  — zawodnicy z weight_category + grade
 *   <key>_fights                     — pojedynki (W-L-D-NC)
 *   <key>_belts / <key>_grades       — system stopni (kyu/dan/pas)
 *   <key>_results                    — results/scoring per round/walka
 */
abstract class CombatSport extends BaseSportArchetype
{
    public function entityTypes(): array
    {
        return ['athlete' => 'fighters', 'event' => 'fights', 'result' => 'results'];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'athlete' => 6,   // 6 zawodnikow w klubie demo
            'event'   => 4,   // 4 walki
            'result'  => 4,   // result per walka
        ];
    }

    public function tables(): array
    {
        $k = $this->key();
        return [
            "{$k}_fighters",   // niektore plugin uzywaja _athletes — mozna nadpisac
            "{$k}_fights",
            "{$k}_results",
        ];
    }
}
