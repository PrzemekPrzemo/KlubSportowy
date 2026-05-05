<?php

namespace App\Sports\Climbing;

use App\Sports\Support\NicheSport;

/**
 * Climbing (PZA) — NicheSport archetype.
 *
 * Schema dependencies:
 *   climbing_routes (parent — no member)
 *   climbing_sends  (child — route_id REQUIRED FK)
 *   climbing_results (member_id, competition_*)
 *
 * Tabele MUSZA byc w kolejnosci dependency: routes → sends → results.
 */
class ClimbingArchetype extends NicheSport
{
    public function key(): string
    {
        return 'climbing';
    }

    public function tables(): array
    {
        return ['climbing_routes', 'climbing_sends', 'climbing_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
