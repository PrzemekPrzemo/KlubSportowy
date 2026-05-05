<?php

namespace App\Sports\SkiJump;

use App\Sports\Support\TimingSport;

/**
 * SkiJump — TimingSport archetype.
 *
 * Tabela: ski_jump_results.
 */
class SkiJumpArchetype extends TimingSport
{
    public function key(): string
    {
        return 'skijump';
    }

    public function tables(): array
    {
        return ['ski_jump_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
