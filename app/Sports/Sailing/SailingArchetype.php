<?php

namespace App\Sports\Sailing;

use App\Sports\Support\NicheSport;

/**
 * Sailing (PZŻ) — NicheSport archetype.
 *
 * Schema dependencies:
 *   sailing_boats   (parent — name, no member required)
 *   sailing_crew    (child — boat_id REQUIRED + member_id REQUIRED)
 *   sailing_races   (no member — name + race_date)
 *   sailing_licenses (member_id + license_type ENUM default)
 *
 * Tabele MUSZA byc w kolejnosci: boats → crew → races → licenses.
 */
class SailingArchetype extends NicheSport
{
    public function key(): string
    {
        return 'sailing';
    }

    public function tables(): array
    {
        return ['sailing_boats', 'sailing_crew', 'sailing_races', 'sailing_licenses'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
