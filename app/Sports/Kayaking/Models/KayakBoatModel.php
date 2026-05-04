<?php

namespace App\Sports\Kayaking\Models;

use App\Models\ClubScopedModel;

class KayakBoatModel extends ClubScopedModel
{
    protected string $table = 'kayak_boats';

    public static array $BOAT_TYPES = [
        'K1' => 'Kayak 1-osobowy (K1)',
        'C1' => 'Canoe 1-osobowe (C1)',
        'K2' => 'Kayak 2-osobowy (K2)',
        'C2' => 'Canoe 2-osobowe (C2)',
        'K4' => 'Kayak 4-osobowy (K4)',
        'C4' => 'Canoe 4-osobowe (C4)',
    ];

    public static array $STATES = [
        'nowa'       => ['label' => 'Nowa',        'class' => 'success'],
        'dobra'      => ['label' => 'Dobra',       'class' => 'primary'],
        'używana'    => ['label' => 'Używana',     'class' => 'info'],
        'do_serwisu' => ['label' => 'Do serwisu',  'class' => 'warning'],
        'wycofana'   => ['label' => 'Wycofana',    'class' => 'secondary'],
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM kayak_boats WHERE club_id = ? ORDER BY boat_type, name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
