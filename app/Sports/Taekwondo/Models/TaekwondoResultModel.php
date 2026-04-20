<?php

namespace App\Sports\Taekwondo\Models;

use App\Models\ClubScopedModel;

class TaekwondoResultModel extends ClubScopedModel
{
    protected string $table = 'taekwondo_results';

    public static array $CATEGORIES = [
        'kyorugi'  => 'Kyorugi (walka)',
        'poomsae'  => 'Poomsae (formy)',
        'freestyle'=> 'Freestyle',
    ];

    public static array $WEIGHT_CLASSES_MEN = [
        '-54', '-58', '-63', '-68', '-74', '-80', '-87', '+87',
    ];

    public static array $WEIGHT_CLASSES_WOMEN = [
        '-46', '-49', '-53', '-57', '-62', '-67', '-73', '+73',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT tr.*, m.first_name, m.last_name, m.member_number
             FROM taekwondo_results tr
             JOIN members m ON m.id = tr.member_id
             WHERE tr.club_id = ?
             ORDER BY tr.competition_date DESC, m.last_name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
