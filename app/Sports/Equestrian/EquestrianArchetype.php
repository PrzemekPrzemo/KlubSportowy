<?php

namespace App\Sports\Equestrian;

use App\Sports\Support\NicheSport;

/**
 * Equestrian (PZJ) — NicheSport archetype.
 *
 * Konwencja tabel jest unikalna — para rider+horse, hierarchia
 * competitions → classes → starts → results, plus health + training.
 *
 * Kluczowe tabele dla 'demo-ready' (po seedzie minimum 1 wpis):
 *   - equestrian_horses (z migracji 002 + Q.1 extras)
 *   - equestrian_competitions (Q.5)
 *   - equestrian_results
 */
class EquestrianArchetype extends NicheSport
{
    public function key(): string
    {
        return 'equestrian';
    }

    public function tables(): array
    {
        return [
            'equestrian_horses',
            'equestrian_competitions',
            'equestrian_results',
        ];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'horse'       => 5,
            'owner'       => 4,
            'rider'       => 4,
            'competition' => 2,
            'class'       => 6,    // 3 klasy per competition
            'start'       => 12,   // 4 startow per klasa avg
            'result'      => 12,   // 1:1 z startami
            'health'      => 5,    // 1 szczepienie per kon
            'training'    => 8,    // ~2 sesje per kon
        ];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
