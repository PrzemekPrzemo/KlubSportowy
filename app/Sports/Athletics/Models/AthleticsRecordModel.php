<?php

namespace App\Sports\Athletics\Models;

use App\Models\ClubScopedModel;

class AthleticsRecordModel extends ClubScopedModel
{
    protected string $table = 'athletics_records';

    public function listForClub(?int $disciplineId = null, int $page = 1, int $perPage = 25): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ar.*, m.first_name, m.last_name, m.member_number,
                       d.name AS discipline_name
                FROM athletics_records ar
                JOIN members m ON m.id = ar.member_id
                LEFT JOIN disciplines d ON d.id = ar.discipline_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ar.club_id = ?"; $params[] = $clubId; }
        if ($disciplineId) { $sql .= " AND ar.discipline_id = ?"; $params[] = $disciplineId; }
        $sql .= " ORDER BY ar.record_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function personalBests(?int $disciplineId = null, int $limit = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ar.*, m.first_name, m.last_name, d.name AS discipline_name
                FROM athletics_records ar
                JOIN members m ON m.id = ar.member_id
                LEFT JOIN disciplines d ON d.id = ar.discipline_id
                WHERE ar.is_personal_best = 1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ar.club_id = ?"; $params[] = $clubId; }
        if ($disciplineId) { $sql .= " AND ar.discipline_id = ?"; $params[] = $disciplineId; }
        $sql .= " ORDER BY ar.result_value ASC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function clubRecords(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ar.*, m.first_name, m.last_name, d.name AS discipline_name
                FROM athletics_records ar
                JOIN members m ON m.id = ar.member_id
                LEFT JOIN disciplines d ON d.id = ar.discipline_id
                WHERE ar.is_club_record = 1";
        if ($clubId !== null) $sql .= " AND ar.club_id = " . (int)$clubId;
        $sql .= " ORDER BY d.sort_order, d.name";
        return $this->db->query($sql)->fetchAll();
    }

    public static function formatResult(float $val, string $unit): string
    {
        return match ($unit) {
            's'   => number_format($val, 2, '.', '') . ' s',
            'min' => number_format($val, 2, '.', '') . ' min',
            'm'   => number_format($val, 2, ',', '') . ' m',
            'cm'  => number_format($val, 0, ',', '') . ' cm',
            'kg'  => number_format($val, 2, ',', '') . ' kg',
            default => (string)$val,
        };
    }
}
