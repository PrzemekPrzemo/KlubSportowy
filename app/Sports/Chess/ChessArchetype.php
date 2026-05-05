<?php

namespace App\Sports\Chess;

use App\Sports\Support\NicheSport;

/**
 * Chess (PZSzach) — NicheSport archetype.
 *
 * Schema:
 *   chess_ratings (member_id, rating REQUIRED, rating_date REQUIRED)
 *   chess_results (member_id, competition_*, result ENUM REQUIRED no default)
 */
class ChessArchetype extends NicheSport
{
    public function key(): string
    {
        return 'chess';
    }

    public function tables(): array
    {
        return ['chess_ratings', 'chess_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
