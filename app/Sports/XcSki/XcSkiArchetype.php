<?php

namespace App\Sports\XcSki;

use App\Sports\Support\TimingSport;

/**
 * XcSki (cross-country) — TimingSport archetype.
 *
 * Tabela: xc_ski_results (technique ENUM default, distance_km REQUIRED).
 */
class XcSkiArchetype extends TimingSport
{
    public function key(): string
    {
        return 'xcski';
    }

    public function tables(): array
    {
        return ['xc_ski_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
