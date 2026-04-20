<?php

namespace App\Sports\Equestrian\Models;

use App\Models\ClubScopedModel;

class EquestrianHorseModel extends ClubScopedModel
{
    protected string $table = 'equestrian_horses';

    public static array $DISCIPLINES = [
        'dressage'  => 'Ujeżdżenie (Dressage)',
        'jumping'   => 'Skoki przez przeszkody',
        'eventing'  => 'Wszechstronny Konkurs Konia (WKKW)',
        'endurance' => 'Rajdy długodystansowe',
        'reining'   => 'Reining',
        'vaulting'  => 'Woltyżerka',
        'driving'   => 'Powożenie',
    ];

    public static array $SEX = [
        'stallion' => 'Ogier',
        'mare'     => 'Klacz',
        'gelding'  => 'Wałach',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM equestrian_horses WHERE club_id = ? ORDER BY name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
