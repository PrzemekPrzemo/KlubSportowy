<?php

namespace App\Models;

/**
 * Rezerwacje zasobow klubu (bookings).
 *
 * Conflict detection: SELECT przed INSERT (start_at < new_end AND end_at > new_start)
 * filtrowany po resource_id + club_id + status not in (cancelled, no_show).
 *
 * Calendar feed: forCalendar() zwraca format zgodny z FullCalendar.js v6.
 */
class BookingModel extends ClubScopedModel
{
    protected string $table = 'bookings';

    /**
     * Czy slot jest wolny dla danego zasobu.
     * Zwraca true gdy nie ma konfliktow.
     */
    public function isAvailable(int $resourceId, string $start, string $end, ?int $excludeId = null): bool
    {
        $clubId = $this->clubId();
        $sql = "SELECT COUNT(*) FROM bookings
                WHERE resource_id = ?
                  AND status NOT IN ('cancelled', 'no_show')
                  AND start_at < ?
                  AND end_at > ?";
        $params = [$resourceId, $end, $start];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * Eventy dla FullCalendar.js — format extendedProps + color z resource.
     *
     * @param string   $from        YYYY-MM-DD lub Y-m-d H:i:s
     * @param string   $to          YYYY-MM-DD lub Y-m-d H:i:s
     * @param int|null $resourceId  filtr opcjonalny
     */
    public function forCalendar(string $from, string $to, ?int $resourceId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT b.id, b.title, b.start_at, b.end_at, b.status,
                       b.resource_id, b.member_id,
                       r.name AS resource_name, r.color AS resource_color,
                       m.first_name AS member_first, m.last_name AS member_last
                FROM bookings b
                JOIN bookable_resources r ON r.id = b.resource_id
                LEFT JOIN members m ON m.id = b.member_id
                WHERE b.start_at < ? AND b.end_at > ?
                  AND b.status NOT IN ('cancelled')";
        $params = [$to, $from];
        if ($clubId !== null) {
            $sql .= " AND b.club_id = ?";
            $params[] = $clubId;
        }
        if ($resourceId !== null) {
            $sql .= " AND b.resource_id = ?";
            $params[] = $resourceId;
        }
        $sql .= " ORDER BY b.start_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $events = [];
        foreach ($rows as $r) {
            $title = $r['title'];
            if (!empty($r['resource_name'])) {
                $title .= ' (' . $r['resource_name'] . ')';
            }
            $events[] = [
                'id'    => (int)$r['id'],
                'title' => $title,
                'start' => str_replace(' ', 'T', $r['start_at']),
                'end'   => str_replace(' ', 'T', $r['end_at']),
                'color' => $r['resource_color'] ?: '#6c757d',
                'extendedProps' => [
                    'resource_id'   => (int)$r['resource_id'],
                    'resource_name' => $r['resource_name'],
                    'status'        => $r['status'],
                    'member_name'   => trim(($r['member_first'] ?? '') . ' ' . ($r['member_last'] ?? '')) ?: null,
                ],
            ];
        }
        return $events;
    }

    /**
     * Booking z join'em do zasobu + bookera dla widoku detail.
     */
    public function findWithJoins(int $id): ?array
    {
        $clubId = $this->clubId();
        $sql = "SELECT b.*, r.name AS resource_name, r.color AS resource_color, r.type AS resource_type,
                       m.first_name AS member_first, m.last_name AS member_last, m.email AS member_email,
                       u.full_name AS booked_by_name
                FROM bookings b
                JOIN bookable_resources r ON r.id = b.resource_id
                LEFT JOIN members m ON m.id = b.member_id
                LEFT JOIN users u   ON u.id = b.booked_by_user_id
                WHERE b.id = ?";
        $params = [$id];
        if ($clubId !== null) {
            $sql .= " AND b.club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lista bookings (tabular) z paginacja.
     */
    public function listPaginated(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT b.*, r.name AS resource_name, r.color AS resource_color,
                       m.first_name AS member_first, m.last_name AS member_last
                FROM bookings b
                JOIN bookable_resources r ON r.id = b.resource_id
                LEFT JOIN members m ON m.id = b.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND b.club_id = ?";
            $params[] = $clubId;
        }
        if ($status !== null && $status !== '') {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY b.start_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Bookings dla danego member'a (member portal).
     */
    public function listForMember(int $memberId, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT b.*, r.name AS resource_name, r.color AS resource_color
                FROM bookings b
                JOIN bookable_resources r ON r.id = b.resource_id
                WHERE b.member_id = ?";
        $params = [$memberId];
        if ($clubId !== null) {
            $sql .= " AND b.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY b.start_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }
}
