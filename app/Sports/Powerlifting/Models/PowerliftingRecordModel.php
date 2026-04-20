<?php

namespace App\Sports\Powerlifting\Models;

use App\Models\ClubScopedModel;

class PowerliftingRecordModel extends ClubScopedModel
{
    protected string $table = 'powerlifting_records';

    public static array $LIFT_TYPES = [
        'squat'    => 'Przysiad (Squat)',
        'bench'    => 'Wyciskanie (Bench Press)',
        'deadlift' => 'Martwy ciąg (Deadlift)',
        'total'    => 'Suma (Total)',
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT pr.*, m.first_name, m.last_name, m.member_number
                FROM powerlifting_records pr
                JOIN members m ON m.id = pr.member_id
                WHERE pr.club_id = ?
                ORDER BY pr.lift_type, pr.weight_class, pr.weight_kg DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns best record per lift_type + weight_class combination.
     */
    public function clubRecords(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT pr.lift_type, pr.weight_class, MAX(pr.weight_kg) AS weight_kg,
                       m.first_name, m.last_name, pr.set_date, pr.competition
                FROM powerlifting_records pr
                JOIN members m ON m.id = pr.member_id
                WHERE pr.club_id = ?
                GROUP BY pr.lift_type, pr.weight_class
                ORDER BY FIELD(pr.lift_type,'squat','bench','deadlift','total'), pr.weight_class";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
