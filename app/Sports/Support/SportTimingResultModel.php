<?php

namespace App\Sports\Support;

use App\Models\ClubScopedModel;
use PDO;

/**
 * Wspólny model wyników dla 11 timing sportów (swimming, cycling, rowing,
 * triathlon, biathlon, alpineski, xcski, skijump, snowboard, rollerskating,
 * kayaking) — tabela `sport_timing_results` z discriminator `sport_key`.
 *
 * Multi-tenant: wszystkie zapytania filtrują po `club_id` z ClubContext.
 */
class SportTimingResultModel extends ClubScopedModel
{
    protected string $table = 'sport_timing_results';

    /**
     * Format milisekund jako m:ss.cc (minutes:seconds.centiseconds)
     */
    public static function formatTime(int $ms): string
    {
        $totalCs  = (int)round($ms / 10);
        $cs       = $totalCs % 100;
        $totalSec = (int)($totalCs / 100);
        $sec      = $totalSec % 60;
        $min      = (int)($totalSec / 60);
        return sprintf('%d:%02d.%02d', $min, $sec, $cs);
    }

    /**
     * Lista wyników klubu (opcjonalnie filtrowana po sport + member + event).
     */
    public function listForClubSport(string $sportKey, ?int $memberId = null, ?string $eventName = null, int $page = 1, int $perPage = 30): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT r.*, m.first_name, m.last_name, m.member_number
                   FROM sport_timing_results r
                   JOIN members m ON m.id = r.member_id
                   WHERE r.club_id = ? AND r.sport_key = ?";
        $params = [$clubId, $sportKey];
        if ($memberId !== null) {
            $sql      .= " AND r.member_id = ?";
            $params[] = $memberId;
        }
        if ($eventName !== null && $eventName !== '') {
            $sql      .= " AND r.event_name = ?";
            $params[] = $eventName;
        }
        $sql .= " ORDER BY r.recorded_at DESC, r.finish_time_ms ASC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Dostępne event_name dla sportu w klubie — do filtra w UI.
     */
    public function eventNames(string $sportKey): array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT DISTINCT event_name FROM sport_timing_results
             WHERE club_id = ? AND sport_key = ? ORDER BY event_name"
        );
        $stmt->execute([$clubId, $sportKey]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Personal best per event_name dla zawodnika.
     */
    public function personalBests(int $memberId, string $sportKey): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT event_name, distance_m, MIN(finish_time_ms) AS best_time_ms, MAX(recorded_at) AS last_at
                   FROM sport_timing_results
                   WHERE club_id = ? AND sport_key = ? AND member_id = ?
                   GROUP BY event_name, distance_m
                   ORDER BY event_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $sportKey, $memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Pełna historia wyników zawodnika w sporcie — używane do progress charta.
     */
    public function historyForMember(int $memberId, string $sportKey, ?string $eventName = null, int $limit = 200): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT id, event_name, distance_m, finish_time_ms, penalties_seconds,
                          `rank`, category, recorded_at, verified
                   FROM sport_timing_results
                   WHERE club_id = ? AND sport_key = ? AND member_id = ?";
        $params = [$clubId, $sportKey, $memberId];
        if ($eventName !== null && $eventName !== '') {
            $sql      .= " AND event_name = ?";
            $params[] = $eventName;
        }
        $sql .= " ORDER BY recorded_at ASC, id ASC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Weryfikuj wynik (admin) — ustawia verified=1.
     */
    public function verify(int $resultId): bool
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "UPDATE sport_timing_results SET verified = 1 WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$resultId, $clubId]);
    }

    /**
     * Pobierz wynik dla swojego klubu (zwraca null jeśli rekord należy do innego tenanta).
     */
    public function findInClub(int $id): ?array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT * FROM sport_timing_results WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Wstaw wynik z auto-uzupełnieniem club_id z ClubContext.
     */
    public function insertScoped(array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $data['club_id'] = $clubId;
        }
        return $this->insert($data);
    }

    /**
     * Usuń wynik tylko w obrębie własnego klubu.
     */
    public function deleteInClub(int $id): bool
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "DELETE FROM sport_timing_results WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$id, $clubId]);
    }
}
