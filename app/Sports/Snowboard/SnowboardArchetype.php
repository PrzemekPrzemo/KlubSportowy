<?php

namespace App\Sports\Snowboard;

use App\Sports\Support\TimingSport;

/**
 * Snowboard — TimingSport archetype.
 *
 * Tabela: snowboard_results (discipline ENUM REQUIRED bez default).
 */
class SnowboardArchetype extends TimingSport
{
    public function key(): string
    {
        return 'snowboard';
    }

    public function tables(): array
    {
        return ['snowboard_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
