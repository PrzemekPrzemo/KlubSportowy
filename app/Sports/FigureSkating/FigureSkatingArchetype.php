<?php

namespace App\Sports\FigureSkating;

use App\Sports\Support\ScoringSport;

/**
 * FigureSkating — ScoringSport archetype.
 *
 * Tabela: figure_skating_results (z underscorem, nie figureskating_*).
 * discipline ENUM REQUIRED bez default.
 */
class FigureSkatingArchetype extends ScoringSport
{
    public function key(): string
    {
        return 'figureskating';
    }

    public function tables(): array
    {
        return ['figure_skating_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
