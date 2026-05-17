<?php

namespace App\Models;

use App\Sports\Studio\ClassFullException;
use App\Sports\Studio\PassExhaustedException;

/**
 * Rezerwacje konkretnych wystapien klas (data).
 *
 * Logika:
 *   - bookForMember()  — zapisuje (consume pass) lub na waitlist (gdy pelne)
 *   - cancelBooking()  — anuluje + refund pass (jesli w oknie 12h) + promote waitlist
 *   - markAttended()   — check-in przez instruktora
 *   - waitlistPosition() — pozycja w kolejce
 *
 * Idempotency: UNIQUE (schedule_id, member_id, class_date) zapobiega dublom.
 */
class StudioClassBookingModel extends ClubScopedModel
{
    protected string $table = 'studio_class_bookings';

    /** Okno anulacji z refundem pass-a (w godzinach). */
    public const REFUND_WINDOW_HOURS = 12;

    /**
     * Zapis zawodnika na klase.
     *
     * Flow:
     *   1) Walidacja: schedule istnieje w klubie, sport_key, max_capacity
     *   2) Sprawdz UNIQUE — duplikat? zwroc istniejacy
     *   3) consumeOne(pass) w transakcji
     *   4) INSERT booking (status = booked lub waitlist)
     *
     * @return array{id:int,status:string} booking po insercie
     * @throws ClassFullException     gdy pelne i waitlist=false
     * @throws PassExhaustedException gdy pass wyczerpany
     */
    public function bookForMember(int $scheduleId, int $memberId, string $classDate, int $passId, bool $allowWaitlist = true): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('bookForMember requires club context.');
        }

        // 1. Walidacja schedule
        $stmt = $this->db->prepare(
            "SELECT * FROM studio_class_schedules WHERE id = ? AND club_id = ? AND active = 1 LIMIT 1"
        );
        $stmt->execute([$scheduleId, $clubId]);
        $sched = $stmt->fetch();
        if (!$sched) {
            throw new \InvalidArgumentException('Klasa nie istnieje lub nieaktywna w tym klubie.');
        }

        // 2. Idempotency — jezeli juz jest rezerwacja, zwroc ja (nie consume znowu).
        $stmt = $this->db->prepare(
            "SELECT * FROM studio_class_bookings
             WHERE schedule_id = ? AND member_id = ? AND class_date = ?
             LIMIT 1"
        );
        $stmt->execute([$scheduleId, $memberId, $classDate]);
        if ($existing = $stmt->fetch()) {
            if (in_array($existing['status'], ['booked','waitlist','attended'], true)) {
                return $existing;
            }
            // cancelled / no_show — usuwamy stary i zrobimy fresh booking
            $del = $this->db->prepare(
                "DELETE FROM studio_class_bookings WHERE id = ? AND club_id = ?"
            );
            $del->execute([(int)$existing['id'], $clubId]);
        }

        // 3. Sprawdz capacity i zdecyduj status
        $schedModel = new StudioClassScheduleModel();
        $booked = $schedModel->bookedCount($scheduleId, $classDate, ['booked','attended']);
        $isWaitlist = $booked >= (int)$sched['max_capacity'];
        if ($isWaitlist && !$allowWaitlist) {
            throw new ClassFullException('Klasa pelna, waitlist wylaczona.');
        }

        // 4. Consume pass (TYLKO gdy NIE waitlist — waitlist konsumuje pass przy promocji)
        $passModel = new StudioMemberPassModel();
        if (!$isWaitlist) {
            $passModel->consumeOne($passId);
        }

        // 5. INSERT
        $id = $this->insert([
            'club_id'     => $clubId,
            'schedule_id' => $scheduleId,
            'member_id'   => $memberId,
            'pass_id'     => $passId,
            'class_date'  => $classDate,
            'status'      => $isWaitlist ? 'waitlist' : 'booked',
            'booked_at'   => date('Y-m-d H:i:s'),
        ]);

        return [
            'id'     => $id,
            'status' => $isWaitlist ? 'waitlist' : 'booked',
        ];
    }

    /**
     * Anulacja rezerwacji.
     *  - Jesli w oknie REFUND_WINDOW_HOURS przed startem zajec → refund pass'a
     *  - Promote pierwszego z waitlist (consume jego pass, status='booked')
     *  - Zwraca info dla flash message
     */
    public function cancelBooking(int $bookingId): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('cancelBooking requires club context.');
        }

        $stmt = $this->db->prepare(
            "SELECT b.*, s.time_start, s.sport_key
             FROM studio_class_bookings b
             JOIN studio_class_schedules s ON s.id = b.schedule_id
             WHERE b.id = ? AND b.club_id = ?"
        );
        $stmt->execute([$bookingId, $clubId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            throw new \InvalidArgumentException('Rezerwacja nie istnieje.');
        }
        if (in_array($booking['status'], ['cancelled','no_show'], true)) {
            return ['cancelled' => false, 'refunded' => false, 'promoted' => false, 'reason' => 'already_cancelled'];
        }

        // Refund jezeli w oknie
        $classStart = strtotime($booking['class_date'] . ' ' . $booking['time_start']);
        $now        = time();
        $hoursLeft  = ($classStart - $now) / 3600.0;
        $refunded   = false;

        if ($booking['status'] === 'booked' && $hoursLeft >= self::REFUND_WINDOW_HOURS && !empty($booking['pass_id'])) {
            try {
                (new StudioMemberPassModel())->refundOne((int)$booking['pass_id']);
                $refunded = true;
            } catch (\Throwable) {}
        }

        $u = $this->db->prepare(
            "UPDATE studio_class_bookings
             SET status = 'cancelled', cancelled_at = ?
             WHERE id = ? AND club_id = ?"
        );
        $u->execute([date('Y-m-d H:i:s'), $bookingId, $clubId]);

        // Promote waitlist (tylko jezeli cancel pochodzi z booked)
        $promoted = false;
        if ($booking['status'] === 'booked') {
            $promoted = $this->promoteFirstWaitlist((int)$booking['schedule_id'], $booking['class_date']);
        }

        return ['cancelled' => true, 'refunded' => $refunded, 'promoted' => $promoted];
    }

    /**
     * Promuje najwczesniejszego z waitlist → booked (consume pass).
     * Zwraca true jezeli udalo sie.
     */
    public function promoteFirstWaitlist(int $scheduleId, string $classDate): bool
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM studio_class_bookings
             WHERE schedule_id = ? AND class_date = ? AND status = 'waitlist'"
             . ($clubId !== null ? " AND club_id = ?" : "") .
             " ORDER BY booked_at ASC LIMIT 1"
        );
        $params = [$scheduleId, $classDate];
        if ($clubId !== null) $params[] = $clubId;
        $stmt->execute($params);
        $waiter = $stmt->fetch();
        if (!$waiter) return false;

        // Consume pass — jezeli sie nie da (exhausted), zostaw na waitlist
        try {
            if (!empty($waiter['pass_id'])) {
                (new StudioMemberPassModel())->consumeOne((int)$waiter['pass_id']);
            }
        } catch (\Throwable) {
            return false;
        }

        $u = $this->db->prepare(
            "UPDATE studio_class_bookings SET status = 'booked' WHERE id = ?"
            . ($clubId !== null ? " AND club_id = ?" : "")
        );
        $upd = [$waiter['id']];
        if ($clubId !== null) $upd[] = $clubId;
        $u->execute($upd);
        return true;
    }

    /** Check-in. */
    public function markAttended(int $bookingId): bool
    {
        $clubId = $this->clubId();
        $sql = "UPDATE studio_class_bookings
                SET status = 'attended', attended_at = ?
                WHERE id = ? AND status IN ('booked','attended')";
        $params = [date('Y-m-d H:i:s'), $bookingId];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Mark no-show (dla nieobecnych po zajeciach). */
    public function markNoShow(int $bookingId): bool
    {
        $clubId = $this->clubId();
        $sql = "UPDATE studio_class_bookings SET status = 'no_show'
                WHERE id = ? AND status = 'booked'";
        $params = [$bookingId];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Pozycja na liscie waitlist (1-based). 0 = brak na waitlist. */
    public function waitlistPosition(int $bookingId): int
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT schedule_id, class_date, booked_at FROM studio_class_bookings
             WHERE id = ?"
             . ($clubId !== null ? " AND club_id = ?" : "")
        );
        $params = [$bookingId];
        if ($clubId !== null) $params[] = $clubId;
        $stmt->execute($params);
        $b = $stmt->fetch();
        if (!$b) return 0;

        $sql = "SELECT COUNT(*) FROM studio_class_bookings
                WHERE schedule_id = ? AND class_date = ? AND status = 'waitlist'
                  AND booked_at <= ?";
        $params = [$b['schedule_id'], $b['class_date'], $b['booked_at']];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Roster instruktora — zapisani na konkretne wystapienie. */
    public function roster(int $scheduleId, string $classDate): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT b.*, m.first_name, m.last_name, m.member_number
                FROM studio_class_bookings b
                JOIN members m ON m.id = b.member_id
                WHERE b.schedule_id = ? AND b.class_date = ?";
        $params = [$scheduleId, $classDate];
        if ($clubId !== null) {
            $sql .= " AND b.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY FIELD(b.status,'booked','attended','waitlist','no_show','cancelled'), b.booked_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Nadchodzace rezerwacje zawodnika (od dzisiaj). */
    public function upcomingForMember(int $memberId, int $limit = 20): array
    {
        $clubId = $this->clubId();
        $today  = date('Y-m-d');
        $sql = "SELECT b.*, s.name AS class_name, s.time_start, s.duration_min,
                       s.room, s.sport_key, s.day_of_week
                FROM studio_class_bookings b
                JOIN studio_class_schedules s ON s.id = b.schedule_id
                WHERE b.member_id = ?
                  AND b.class_date >= ?
                  AND b.status IN ('booked','waitlist','attended')";
        $params = [$memberId, $today];
        if ($clubId !== null) {
            $sql .= " AND b.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY b.class_date ASC, s.time_start ASC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
