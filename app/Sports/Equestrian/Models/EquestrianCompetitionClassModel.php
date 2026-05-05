<?php

namespace App\Sports\Equestrian\Models;

use App\Models\BaseModel;

/**
 * Klasy w zawodach jezdzieckich (Q.5).
 * Note: nie dziedzczy ClubScopedModel bo competition_classes nie ma club_id —
 * scoping przez competition_id (FK do equestrian_competitions ktore ma club_id).
 */
class EquestrianCompetitionClassModel extends BaseModel
{
    protected string $table = 'equestrian_competition_classes';

    public static array $DISCIPLINES = [
        'dressage'  => 'Ujeżdżenie',
        'jumping'   => 'Skoki',
        'eventing'  => 'WKKW',
        'endurance' => 'Rajdy',
        'reining'   => 'Reining',
        'vaulting'  => 'Woltyżerka',
        'driving'   => 'Powożenie',
        'para'      => 'Parajeździectwo',
    ];

    public static array $LEVELS = [
        'LL'         => 'LL — najnizsza',
        'L'          => 'L',
        'P'          => 'P',
        'N1'         => 'N1',
        'N2'         => 'N2',
        'C'          => 'C',
        'CC'         => 'CC',
        'Grand Prix' => 'Grand Prix',
    ];

    public function listForCompetition(int $competitionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM equestrian_competition_classes
             WHERE competition_id = ?
             ORDER BY class_no, id"
        );
        $stmt->execute([$competitionId]);
        return $stmt->fetchAll();
    }
}
