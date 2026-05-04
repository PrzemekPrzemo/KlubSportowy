<?php

namespace App\Sports\DanceSport\Models;

use App\Models\ClubScopedModel;

class DanceCoupleModel extends ClubScopedModel
{
    protected string $table = 'dance_couples';

    public static array $DISCIPLINES = [
        'standard'           => 'Standardowe (walc, tango, foxtrot, quickstep, walc wiedeński)',
        'latin'              => 'Latynoamerykańskie (samba, cha-cha, rumba, paso, jive)',
        'ten_dances'         => '10 tańców',
        'formation_standard' => 'Formacje standardowe',
        'formation_latin'    => 'Formacje latynoamerykańskie',
    ];

    public static array $CLASSES = [
        'D' => 'Klasa D (początkująca)',
        'C' => 'Klasa C',
        'B' => 'Klasa B',
        'A' => 'Klasa A',
        'S' => 'Klasa S',
        'M' => 'Klasa M (mistrzowska)',
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT dc.*,
                    ml.first_name AS leader_first, ml.last_name AS leader_last,
                    mf.first_name AS follower_first, mf.last_name AS follower_last
             FROM dance_couples dc
             JOIN members ml ON ml.id = dc.leader_id
             LEFT JOIN members mf ON mf.id = dc.follower_id
             WHERE dc.club_id = ?
             ORDER BY dc.class_level DESC, ml.last_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
