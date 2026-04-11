<?php

namespace App\Models;

class TrainingModel extends ClubScopedModel
{
    protected string $table = 'trainings';

    public function listForClub(?int $clubSportId = null, ?string $from = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT t.*, s.name AS sport_name, s.color AS sport_color,
                          u.full_name AS instructor_name,
                          (SELECT COUNT(*) FROM training_attendees WHERE training_id = t.id AND status IN ('zapisany','obecny')) AS attendees_count
                   FROM trainings t
                   LEFT JOIN sports s ON s.id = t.sport_id
                   LEFT JOIN users u  ON u.id = t.instructor_id
                   WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND t.club_id = ?"; $params[] = $clubId; }
        if ($clubSportId !== null) { $sql .= " AND t.club_sport_id = ?"; $params[] = $clubSportId; }
        if ($from !== null) { $sql .= " AND t.start_time >= ?"; $params[] = $from; }
        $sql .= " ORDER BY t.start_time DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function upcomingForClub(int $limit = 5): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT t.*, s.name AS sport_name
                FROM trainings t
                LEFT JOIN sports s ON s.id = t.sport_id
                WHERE t.start_time >= NOW() AND t.status = 'zaplanowany'";
        if ($clubId !== null) $sql .= " AND t.club_id = " . (int)$clubId;
        $sql .= " ORDER BY t.start_time ASC LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll();
    }

    public function withAttendees(int $id): ?array
    {
        $row = $this->findById($id);
        if ($row === null) return null;
        $stmt = $this->db->prepare(
            "SELECT ta.*, m.first_name, m.last_name, m.member_number
             FROM training_attendees ta
             JOIN members m ON m.id = ta.member_id
             WHERE ta.training_id = ?
             ORDER BY m.last_name, m.first_name"
        );
        $stmt->execute([$id]);
        $row['attendees'] = $stmt->fetchAll();
        return $row;
    }
}
