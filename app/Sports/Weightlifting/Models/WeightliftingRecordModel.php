<?php

namespace App\Sports\Weightlifting\Models;

use App\Models\ClubScopedModel;

class WeightliftingRecordModel extends ClubScopedModel
{
    protected string $table = 'weightlifting_records';

    public static array $RECORD_TYPES = [
        'club'     => ['label' => 'Klubowy',  'class' => 'primary'],
        'personal' => ['label' => 'Osobisty', 'class' => 'info'],
        'national' => ['label' => 'Krajowy',  'class' => 'success'],
    ];

    public static array $LIFTS = [
        'rwanie'  => ['label' => 'Rwanie (Snatch)',         'class' => 'warning'],
        'podrzut' => ['label' => 'Podrzut (Clean & Jerk)',  'class' => 'danger'],
        'dwubój'  => ['label' => 'Dwubój (Total)',          'class' => 'dark'],
    ];

    public function listForClub(?string $recordType = null): array
    {
        $sql = "SELECT wr.*, m.first_name, m.last_name, m.member_number
                FROM weightlifting_records wr
                JOIN members m ON m.id = wr.member_id
                WHERE wr.club_id = ?";
        $params = [$this->clubId()];
        if ($recordType !== null && array_key_exists($recordType, self::$RECORD_TYPES)) {
            $sql .= " AND wr.record_type = ?";
            $params[] = $recordType;
        }
        $sql .= " ORDER BY wr.record_type, wr.weight_class, wr.lift, wr.value_kg DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Club records grouped by weight_class+lift, showing only the current record (max value).
     */
    public function clubRecords(): array
    {
        $stmt = $this->db->prepare(
            "SELECT wr.*, m.first_name, m.last_name, m.member_number
             FROM weightlifting_records wr
             JOIN members m ON m.id = wr.member_id
             WHERE wr.club_id = ?
               AND wr.record_type = 'club'
               AND wr.value_kg = (
                   SELECT MAX(w2.value_kg)
                   FROM weightlifting_records w2
                   WHERE w2.club_id = wr.club_id
                     AND w2.record_type = 'club'
                     AND w2.lift = wr.lift
                     AND w2.weight_class = wr.weight_class
               )
             GROUP BY wr.weight_class, wr.lift
             ORDER BY wr.weight_class, wr.lift"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
