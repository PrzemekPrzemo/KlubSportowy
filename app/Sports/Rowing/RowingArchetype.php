<?php

namespace App\Sports\Rowing;

use App\Sports\Support\TimingSport;

/**
 * Rowing — TimingSport archetype.
 *
 * Schema: rowing_results
 */
class RowingArchetype extends TimingSport
{
    public function key(): string
    {
        return 'rowing';
    }

    public function tables(): array
    {
        return ['rowing_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
