<?php

namespace App\Sports\AlpineSki;

use App\Sports\Support\TimingSport;

/**
 * AlpineSki — TimingSport archetype.
 *
 * Tabela: alpine_ski_results (z underscorem, nie alpineski_*).
 * discipline ENUM REQUIRED bez default.
 */
class AlpineSkiArchetype extends TimingSport
{
    public function key(): string
    {
        return 'alpineski';
    }

    public function tables(): array
    {
        return ['alpine_ski_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
