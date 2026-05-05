<?php

namespace App\Sports\Bridge;

use App\Sports\Support\NicheSport;

/**
 * Bridge (PZBS) — NicheSport archetype.
 *
 * Schema:
 *   bridge_partnerships (player1_id+player2_id, category ENUM default)
 *   bridge_tournaments  (name + tournament_type ENUM default + tournament_date)
 */
class BridgeArchetype extends NicheSport
{
    public function key(): string
    {
        return 'bridge';
    }

    public function tables(): array
    {
        return ['bridge_partnerships', 'bridge_tournaments'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
