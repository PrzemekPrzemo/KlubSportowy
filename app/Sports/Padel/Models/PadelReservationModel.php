<?php

namespace App\Sports\Padel\Models;

use App\Models\ClubScopedModel;

class PadelReservationModel extends ClubScopedModel
{
    protected string $table = 'padel_reservations';

    public function weeklyCalendar(int $courtId, string $weekStart): array
    {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +7 days'));
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name
             FROM padel_reservations r
             JOIN members m ON m.id = r.member_id
             WHERE r.court_id = ? AND r.club_id = ?
               AND r.start_datetime >= ? AND r.start_datetime < ?
               AND r.status != 'cancelled'
             ORDER BY r.start_datetime"
        );
        $stmt->execute([$courtId, $this->clubId(), $weekStart, $weekEnd]);
        return $stmt->fetchAll();
    }

    public function reservationsForMember(int $memberId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, c.name AS court_name
             FROM padel_reservations r
             JOIN padel_courts c ON c.id = r.court_id
             WHERE r.member_id = ? AND r.club_id = ?
             ORDER BY r.start_datetime DESC
             LIMIT ?"
        );
        $stmt->execute([$memberId, $this->clubId(), $limit]);
        return $stmt->fetchAll();
    }

    public function isAvailable(int $courtId, string $start, string $end): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM padel_reservations
             WHERE court_id = ? AND club_id = ? AND status != 'cancelled'
               AND start_datetime < ? AND end_datetime > ?"
        );
        $stmt->execute([$courtId, $this->clubId(), $end, $start]);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function listForClub(?string $status = null): array
    {
        $sql    = "SELECT r.*, m.first_name, m.last_name, c.name AS court_name
                   FROM padel_reservations r
                   JOIN members m ON m.id = r.member_id
                   JOIN padel_courts c ON c.id = r.court_id
                   WHERE r.club_id = ?";
        $params = [$this->clubId()];
        if ($status) { $sql .= " AND r.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY r.start_datetime DESC LIMIT 200";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
