<?php
declare(strict_types=1);

namespace App\Helpers\Scheduling;

use App\Helpers\Database;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;

/**
 * TrainerScheduleService — sprawdza dostepnosc trenera i wykrywa konflikty
 * planowania (cross-club aware).
 *
 * Typy konfliktow:
 *   - overlap            : nakladanie sie z innym treningiem (cross-club)
 *   - outside_availability: poza zadeklarowanymi godzinami dostepnosci
 *   - during_leave       : w trakcie urlopu/nieobecnosci
 *   - double_booking     : dwa treningi tego samego trenera w tym samym oknie
 *                          (specjalny przypadek overlap, oznaczony osobno)
 *
 * Trener moze byc w wielu klubach — `trainer_availability.club_id` moze byc
 * NULL (globalna dostepnosc) lub konkretny klub (ograniczenie per-klub).
 * Urlopy (`trainer_leaves`) zawsze sa globalne — trener na urlopie nie
 * pracuje w zadnym klubie.
 */
class TrainerScheduleService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::pdo();
    }

    /**
     * Sprawdza czy trener jest dostepny w danym oknie czasowym.
     *
     * @return array{available: bool, reasons: string[]}
     */
    public function isAvailable(
        int $userId,
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?int $clubId = null
    ): array {
        $conflicts = $this->detectConflicts($userId, $start, $end, null, $clubId);
        if (empty($conflicts)) {
            return ['available' => true, 'reasons' => []];
        }
        $reasons = array_map(static fn(array $c): string => $c['details'] ?? $c['type'], $conflicts);
        return ['available' => false, 'reasons' => $reasons];
    }

    /**
     * Wykrywa konflikty trenera dla zadanego okna czasowego.
     *
     * @param int|null $excludeTrainingId Pomin trening o tym ID (przy update).
     * @param int|null $clubId            Klub kontekstu (dla outside_availability per-club).
     * @return array<int, array{type:string, starts_at:string, ends_at:string, training_id:?int, club_id:?int, details:string}>
     */
    public function detectConflicts(
        int $userId,
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?int $excludeTrainingId = null,
        ?int $clubId = null
    ): array {
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr   = $end->format('Y-m-d H:i:s');
        if ($endStr <= $startStr) {
            // End <= start: degeneracja, traktuj jako 1h okno (chronimy regex'y).
            $end    = (new DateTimeImmutable($startStr))->modify('+1 hour');
            $endStr = $end->format('Y-m-d H:i:s');
        }

        $conflicts = [];

        // 1) Overlap / double-booking — inne treningi tego samego trenera (cross-club)
        $sql = "SELECT t.id, t.club_id, t.name, t.start_time, t.end_time,
                       COALESCE(t.end_time, DATE_ADD(t.start_time, INTERVAL 1 HOUR)) AS effective_end
                FROM trainings t
                WHERE t.instructor_id = :uid
                  AND t.status IN ('zaplanowany','w_trakcie')
                  AND t.start_time < :end_dt
                  AND COALESCE(t.end_time, DATE_ADD(t.start_time, INTERVAL 1 HOUR)) > :start_dt";
        $params = [':uid' => $userId, ':start_dt' => $startStr, ':end_dt' => $endStr];
        if ($excludeTrainingId !== null) {
            $sql .= " AND t.id <> :ex_id";
            $params[':ex_id'] = $excludeTrainingId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sameClub = ($clubId !== null && (int)$row['club_id'] === $clubId);
            $type     = $sameClub ? 'double_booking' : 'overlap';
            $conflicts[] = [
                'type'        => $type,
                'starts_at'   => (string)$row['start_time'],
                'ends_at'     => (string)$row['effective_end'],
                'training_id' => (int)$row['id'],
                'club_id'     => (int)$row['club_id'],
                'details'     => sprintf(
                    '%s z treningiem "%s" (klub #%d, %s - %s)',
                    $type === 'double_booking' ? 'Podwojna rezerwacja' : 'Nakladanie czasowe (cross-club)',
                    (string)$row['name'],
                    (int)$row['club_id'],
                    (string)$row['start_time'],
                    (string)$row['effective_end']
                ),
            ];
        }

        // 2) Urlopy / nieobecnosci (trainer_leaves) — zawsze globalne
        $leaveStmt = $this->db->prepare(
            "SELECT id, leave_type, date_from, date_to, reason
             FROM trainer_leaves
             WHERE user_id = :uid
               AND date_from <= :end_date
               AND date_to   >= :start_date"
        );
        $leaveStmt->execute([
            ':uid'        => $userId,
            ':start_date' => $start->format('Y-m-d'),
            ':end_date'   => $end->format('Y-m-d'),
        ]);
        foreach ($leaveStmt->fetchAll(PDO::FETCH_ASSOC) as $leave) {
            $conflicts[] = [
                'type'        => 'during_leave',
                'starts_at'   => $startStr,
                'ends_at'     => $endStr,
                'training_id' => null,
                'club_id'     => $clubId,
                'details'     => sprintf(
                    'Trener na urlopie (%s, %s - %s)%s',
                    (string)$leave['leave_type'],
                    (string)$leave['date_from'],
                    (string)$leave['date_to'],
                    !empty($leave['reason']) ? ': ' . (string)$leave['reason'] : ''
                ),
            ];
        }

        // 3) Outside availability — sprawdz czy okno miesci sie w zadeklarowanych
        //    slotach dostepnosci. Jezeli trener nie ma w ogole wpisow availability,
        //    nie blokujemy (interpretujemy jako "dostepnosc nieskonfigurowana").
        if ($this->hasAnyAvailability($userId, $clubId)) {
            if (!$this->fitsAvailability($userId, $start, $end, $clubId)) {
                $conflicts[] = [
                    'type'        => 'outside_availability',
                    'starts_at'   => $startStr,
                    'ends_at'     => $endStr,
                    'training_id' => null,
                    'club_id'     => $clubId,
                    'details'     => sprintf(
                        'Termin %s - %s wykracza poza zadeklarowana dostepnosc trenera',
                        $startStr,
                        $endStr
                    ),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Bulk scan: konflikty trenerow dla danego klubu i zakresu dat.
     *
     * @return array<int, array{training_id:int, user_id:int, conflicts: array}>
     */
    public function scanClub(int $clubId, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, instructor_id, start_time, end_time
             FROM trainings
             WHERE club_id = :cid
               AND instructor_id IS NOT NULL
               AND status IN ('zaplanowany','w_trakcie')
               AND start_time >= :from_dt
               AND start_time <= :to_dt"
        );
        $stmt->execute([
            ':cid'     => $clubId,
            ':from_dt' => $from->format('Y-m-d H:i:s'),
            ':to_dt'   => $to->format('Y-m-d H:i:s'),
        ]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $start = new DateTimeImmutable((string)$t['start_time']);
            $end   = !empty($t['end_time'])
                ? new DateTimeImmutable((string)$t['end_time'])
                : $start->modify('+1 hour');
            $cs = $this->detectConflicts((int)$t['instructor_id'], $start, $end, (int)$t['id'], $clubId);
            if (!empty($cs)) {
                $out[] = [
                    'training_id' => (int)$t['id'],
                    'user_id'     => (int)$t['instructor_id'],
                    'conflicts'   => $cs,
                ];
            }
        }
        return $out;
    }

    /**
     * Zapisuje konflikty do tabeli audytu (trainer_schedule_conflicts).
     */
    public function persistConflicts(int $userId, int $clubId, ?int $trainingId, array $conflicts): void
    {
        if (empty($conflicts)) return;
        $stmt = $this->db->prepare(
            "INSERT INTO trainer_schedule_conflicts
                (user_id, training_id, club_id, conflict_type, starts_at, ends_at, details)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($conflicts as $c) {
            $stmt->execute([
                $userId,
                $trainingId,
                $clubId,
                $c['type'],
                $c['starts_at'],
                $c['ends_at'],
                $c['details'] ?? null,
            ]);
        }
    }

    // ── helpers ───────────────────────────────────────────────

    private function hasAnyAvailability(int $userId, ?int $clubId): bool
    {
        $sql = "SELECT 1 FROM trainer_availability WHERE user_id = ?";
        $params = [$userId];
        if ($clubId !== null) {
            // Liczy sie KAZDA availability (globalna lub per-tego-klub) — bo brak globalnej
            // ale wpisy per-inny-klub tez znaczy ze trener konfiguruje wszystko per-klub.
            $sql .= " AND (club_id IS NULL OR club_id = ?)";
            $params[] = $clubId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Czy [start, end] miesci sie w slotach dostepnosci trenera?
     * - Bierze pod uwage tylko sloty obowiazujace w dacie startu (valid_from/until).
     * - W przypadku okna obejmujacego pelnoc — sprawdza oba dni osobno.
     * - club_id NULL w availability = globalna; inaczej musi zgadzac sie z $clubId.
     */
    private function fitsAvailability(
        int $userId,
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?int $clubId
    ): bool {
        $startDay = $start->format('Y-m-d');
        $endDay   = $end->format('Y-m-d');

        // Single-day window
        if ($startDay === $endDay) {
            return $this->daySegmentFits(
                $userId,
                $start,
                $start->format('H:i:s'),
                $end->format('H:i:s'),
                $clubId
            );
        }

        // Multi-day: rozbij na dni i sprawdz kazdy
        $cursor = new DateTimeImmutable($startDay . ' 00:00:00');
        $endDt  = new DateTimeImmutable($endDay . ' 00:00:00');
        while ($cursor <= $endDt) {
            $dayStart = ($cursor->format('Y-m-d') === $startDay)
                ? $start->format('H:i:s')
                : '00:00:00';
            $dayEnd = ($cursor->format('Y-m-d') === $endDay)
                ? $end->format('H:i:s')
                : '23:59:59';
            if (!$this->daySegmentFits($userId, $cursor, $dayStart, $dayEnd, $clubId)) {
                return false;
            }
            $cursor = $cursor->modify('+1 day');
        }
        return true;
    }

    private function daySegmentFits(
        int $userId,
        DateTimeInterface $day,
        string $timeStart,
        string $timeEnd,
        ?int $clubId
    ): bool {
        // weekday: 1=mon..7=sun (zgodnie z N w ISO-8601)
        $weekday = (int)$day->format('N');
        $dateStr = $day->format('Y-m-d');

        $sql = "SELECT time_start, time_end FROM trainer_availability
                WHERE user_id = ? AND weekday = ?
                  AND (valid_from IS NULL OR valid_from <= ?)
                  AND (valid_until IS NULL OR valid_until >= ?)";
        $params = [$userId, $weekday, $dateStr, $dateStr];
        if ($clubId !== null) {
            $sql .= " AND (club_id IS NULL OR club_id = ?)";
            $params[] = $clubId;
        } else {
            $sql .= " AND club_id IS NULL";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($slots)) return false;

        // Czy istnieje slot w pelni pokrywajacy [timeStart, timeEnd]?
        foreach ($slots as $slot) {
            if ($slot['time_start'] <= $timeStart && $slot['time_end'] >= $timeEnd) {
                return true;
            }
        }
        return false;
    }
}
