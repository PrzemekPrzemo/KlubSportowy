<?php

namespace App\Sports\Equestrian\Models;

use App\Models\ClubScopedModel;

/**
 * Wlasciciele koni klubu jezdzieckiego (zewnetrzni i wewnetrzni).
 *
 * Powiazani z member_id (opcjonalnie) gdy wlasciciel jest tez zawodnikiem
 * klubu. Schema z migracji 002_equestrian_owners_extras.
 */
class EquestrianHorseOwnerModel extends ClubScopedModel
{
    protected string $table = 'equestrian_horse_owners';

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*,
                    m.first_name AS member_first_name,
                    m.last_name  AS member_last_name
             FROM equestrian_horse_owners o
             LEFT JOIN members m ON m.id = o.member_id
             WHERE o.club_id = ?
             ORDER BY o.full_name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    /**
     * Zwraca pary [id => "Pelne imie (czlonek/zewnetrzny)"] dla dropdown'u
     * wyboru wlasciciela.
     */
    public function options(): array
    {
        $rows = $this->listForClub();
        $out  = [];
        foreach ($rows as $r) {
            $label = $r['full_name'];
            if (!empty($r['member_id'])) {
                $label .= ' (członek klubu)';
            } else {
                $label .= ' (zewnętrzny)';
            }
            $out[(int)$r['id']] = $label;
        }
        return $out;
    }
}
