<?php

namespace App\Sports\Kayaking;

use App\Sports\Support\TimingSport;

/**
 * Kayaking — TimingSport archetype.
 *
 * Uwaga: tabele uzywaja prefix `kayak_` (nie `kayaking_*`):
 *   kayak_boats, kayak_results
 */
class KayakingArchetype extends TimingSport
{
    public function key(): string
    {
        return 'kayaking';
    }

    public function tables(): array
    {
        return ['kayak_results', 'kayak_boats'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
