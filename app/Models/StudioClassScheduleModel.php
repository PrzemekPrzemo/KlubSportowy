<?php

namespace App\Models;

/**
 * Tygodniowy harmonogram klas studio (yoga / fitness / pilates).
 * Multi-tenant przez ClubScopedModel (filtr automatyczny po club_id).
 */
class StudioClassScheduleModel extends ClubScopedModel
{
    protected string $table = 'studio_class_schedules';

    public const DIFFICULTIES = ['beginner','intermediate','advanced','open'];

    /** Lista klas dla danego sportu (yoga|fitness|pilates), tylko aktywne domyslnie. */
    public function listForSport(string $sportKey, bool $activeOnly = true): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT s.*, u.full_name AS instructor_name
                FROM studio_class_schedules s
                LEFT JOIN users u ON u.id = s.instructor_user_id
                WHERE s.sport_key = ?";
        $params = [$sportKey];
        if ($clubId !== null) {
            $sql .= " AND s.club_id = ?";
            $params[] = $clubId;
        }
        if ($activeOnly) {
            $sql .= " AND s.active = 1";
        }
        $sql .= " ORDER BY s.day_of_week, s.time_start";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Tygodniowy harmonogram pogrupowany po dniach (klucze 1..7). */
    public function weeklyMatrix(string $sportKey): array
    {
        $matrix = array_fill(1, 7, []);
        foreach ($this->listForSport($sportKey, true) as $row) {
            $matrix[(int)$row['day_of_week']][] = $row;
        }
        return $matrix;
    }

    /**
     * Liczba zarezerwowanych miejsc na konkretne wystapienie klasy (data).
     * Multi-tenant safe — zaweza po club_id z kontekstu (jesli ustawiony).
     */
    public function bookedCount(int $scheduleId, string $date, array $statuses = ['booked','attended']): int
    {
        $clubId = $this->clubId();
        $place  = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "SELECT COUNT(*) FROM studio_class_bookings
                WHERE schedule_id = ? AND class_date = ? AND status IN ({$place})";
        $params = [$scheduleId, $date, ...$statuses];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
