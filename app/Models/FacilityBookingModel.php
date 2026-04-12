<?php

namespace App\Models;

class FacilityBookingModel extends ClubScopedModel
{
    protected string $table = 'facility_bookings';

    /**
     * Rezerwacje dla danego obiektu w zakresie dat.
     */
    public function forFacility(int $facilityId, string $from, string $to): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT fb.*, u.full_name AS booked_by_name,
                       m.first_name AS member_first, m.last_name AS member_last
                FROM facility_bookings fb
                LEFT JOIN users u   ON u.id = fb.booked_by
                LEFT JOIN members m ON m.id = fb.booked_for_id
                WHERE fb.facility_id = ?
                  AND fb.start_time < ? AND fb.end_time > ?
                  AND fb.status != 'cancelled'";
        $params = [$facilityId, $to, $from];
        if ($clubId !== null) {
            $sql .= " AND fb.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY fb.start_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Rezerwacje na dany dzień (wszystkie obiekty).
     */
    public function forDate(string $date): array
    {
        $clubId = $this->clubId();
        $from = $date . ' 00:00:00';
        $to   = $date . ' 23:59:59';
        $sql = "SELECT fb.*, f.name AS facility_name, u.full_name AS booked_by_name
                FROM facility_bookings fb
                JOIN facilities f ON f.id = fb.facility_id
                LEFT JOIN users u ON u.id = fb.booked_by
                WHERE fb.start_time <= ? AND fb.end_time >= ?
                  AND fb.status != 'cancelled'";
        $params = [$to, $from];
        if ($clubId !== null) {
            $sql .= " AND fb.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY fb.start_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Sprawdzanie konfliktów rezerwacji.
     */
    public function conflicts(int $facilityId, string $start, string $end, ?int $excludeId = null): array
    {
        $sql = "SELECT * FROM facility_bookings
                WHERE facility_id = ?
                  AND start_time < ? AND end_time > ?
                  AND status != 'cancelled'";
        $params = [$facilityId, $end, $start];
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Moje rezerwacje (jako użytkownik).
     */
    public function myBookings(int $userId, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT fb.*, f.name AS facility_name
                FROM facility_bookings fb
                JOIN facilities f ON f.id = fb.facility_id
                WHERE fb.booked_by = ?";
        $params = [$userId];
        if ($clubId !== null) {
            $sql .= " AND fb.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY fb.start_time DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }
}
