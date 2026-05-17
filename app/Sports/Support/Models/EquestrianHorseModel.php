<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

/**
 * UWAGA: nie myl z istniejacym \App\Sports\Equestrian\Models\EquestrianHorseModel
 * (tabela equestrian_horses). Tu obslugujemy nowa tabele sport_equestrian_horses
 * w schema 106 (FEI ranking, wlasciciel = member_id).
 */
class EquestrianHorseModel extends ClubScopedModel
{
    protected string $table = 'sport_equestrian_horses';

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*,
                    m.first_name AS owner_first, m.last_name AS owner_last
             FROM sport_equestrian_horses h
             LEFT JOIN members m ON m.id = h.owner_member_id
             WHERE h.club_id = ?
             ORDER BY h.name ASC"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
