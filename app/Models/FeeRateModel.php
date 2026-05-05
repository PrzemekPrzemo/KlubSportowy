<?php

namespace App\Models;

class FeeRateModel extends ClubScopedModel
{
    protected string $table = 'fee_rates';

    public function listForClub(?int $sportId = null, bool $onlyActive = true): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return [];

        $sql    = "SELECT fr.*, s.name AS sport_name, s.`key` AS sport_key,
                          mc.name AS class_name
                   FROM fee_rates fr
                   LEFT JOIN sports s         ON s.id = fr.sport_id
                   LEFT JOIN member_classes mc ON mc.id = fr.class_id
                   WHERE fr.club_id = ?";
        $params = [$clubId];
        if ($onlyActive) {
            $sql .= " AND fr.is_active = 1";
        }

        if ($sportId !== null) {
            $sql     .= " AND (fr.sport_id = ? OR fr.sport_id IS NULL)";
            $params[] = $sportId;
        }

        $sql .= " ORDER BY fr.is_active DESC, s.name, fr.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
