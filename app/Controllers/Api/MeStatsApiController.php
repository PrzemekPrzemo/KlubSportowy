<?php

namespace App\Controllers\Api;

use App\Helpers\Database;

/**
 * Mobile-facing stats for the authed member: aggregates over trainings,
 * events, attendance, results (incl. sport-specific extra JSON) and payments.
 *
 * Read-only, member-token-only. The web admin panel has its own broader
 * PlayerStatsController for staff-level cross-member views.
 */
class MeStatsApiController extends BaseApiController
{
    public function summary(): void
    {
        $this->requireMember();
        $db = Database::pdo();

        // Trainings: counts by status across all of the member's training rows
        // for the active club.
        $tr = $db->prepare(
            "SELECT ta.status, COUNT(*) AS cnt
             FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ? AND t.club_id = ?
             GROUP BY ta.status"
        );
        $tr->execute([$this->memberId, $this->clubId]);
        $trainingByStatus = $tr->fetchAll(\PDO::FETCH_KEY_PAIR);

        $trainingTotal = array_sum(array_map('intval', $trainingByStatus));
        $trainingPresent = (int)($trainingByStatus['obecny'] ?? 0);
        $attendancePct = $trainingTotal > 0
            ? round(($trainingPresent / $trainingTotal) * 100, 1)
            : null;

        $ev = $db->prepare(
            "SELECT ee.status, COUNT(*) AS cnt
             FROM event_entries ee
             JOIN events e ON e.id = ee.event_id
             WHERE ee.member_id = ? AND e.club_id = ?
             GROUP BY ee.status"
        );
        $ev->execute([$this->memberId, $this->clubId]);
        $eventByStatus = $ev->fetchAll(\PDO::FETCH_KEY_PAIR);

        $resultsAgg = $db->prepare(
            "SELECT COUNT(*) AS total,
                    COUNT(er.place) AS placed,
                    MIN(er.place) AS best_place,
                    AVG(er.score) AS avg_score,
                    MAX(er.score) AS max_score
             FROM event_results er
             JOIN events e ON e.id = er.event_id
             WHERE er.member_id = ? AND e.club_id = ?"
        );
        $resultsAgg->execute([$this->memberId, $this->clubId]);
        $results = $resultsAgg->fetch(\PDO::FETCH_ASSOC) ?: [];

        $payAgg = $db->prepare(
            "SELECT period_year, SUM(amount) AS total
             FROM payments WHERE member_id = ? AND club_id = ?
             GROUP BY period_year ORDER BY period_year DESC LIMIT 5"
        );
        $payAgg->execute([$this->memberId, $this->clubId]);
        $paymentByYear = $payAgg->fetchAll(\PDO::FETCH_ASSOC);

        $this->json([
            'trainings' => [
                'total'          => $trainingTotal,
                'present'        => $trainingPresent,
                'absent'         => (int)($trainingByStatus['nieobecny'] ?? 0),
                'late'           => (int)($trainingByStatus['spozniony'] ?? 0),
                'attendance_pct' => $attendancePct,
            ],
            'events' => [
                'total'        => array_sum(array_map('intval', $eventByStatus)),
                'confirmed'    => (int)($eventByStatus['potwierdzony'] ?? 0),
                'withdrawn'    => (int)($eventByStatus['wycofany'] ?? 0),
                'invited'      => (int)($eventByStatus['zaproszony'] ?? 0),
            ],
            'results' => [
                'total'      => (int)($results['total'] ?? 0),
                'placed'     => (int)($results['placed'] ?? 0),
                'best_place' => $results['best_place'] !== null ? (int)$results['best_place'] : null,
                'avg_score'  => $results['avg_score'] !== null ? round((float)$results['avg_score'], 2) : null,
                'max_score'  => $results['max_score'] !== null ? round((float)$results['max_score'], 2) : null,
            ],
            'payments' => [
                'by_year' => array_map(
                    fn($r) => ['year' => (int)$r['period_year'], 'total' => (float)$r['total']],
                    $paymentByYear
                ),
            ],
        ]);
    }

    public function results(): void
    {
        $this->requireMember();
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 30)));

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT er.id, er.score, er.place, er.extra, er.notes, er.created_at,
                    e.id AS event_id, e.name AS event_name, e.event_date, e.type AS event_type,
                    s.name AS sport_name, s.id AS sport_id
             FROM event_results er
             JOIN events e ON e.id = er.event_id
             LEFT JOIN sports s ON s.id = e.sport_id
             WHERE er.member_id = ? AND e.club_id = ?
             ORDER BY e.event_date DESC, er.id DESC
             LIMIT $limit"
        );
        $stmt->execute([$this->memberId, $this->clubId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $data = array_map(function (array $r): array {
            $extra = $r['extra'] ?? null;
            if (is_string($extra) && $extra !== '') {
                $decoded = json_decode($extra, true);
                if (is_array($decoded)) $extra = $decoded;
            }
            return [
                'id'         => (int)$r['id'],
                'event_id'   => (int)$r['event_id'],
                'event_name' => $r['event_name'],
                'event_date' => $r['event_date'],
                'event_type' => $r['event_type'],
                'sport_id'   => $r['sport_id'] !== null ? (int)$r['sport_id'] : null,
                'sport_name' => $r['sport_name'],
                'score'      => $r['score'] !== null ? (float)$r['score'] : null,
                'place'      => $r['place'] !== null ? (int)$r['place'] : null,
                'extra'      => $extra,
                'notes'      => $r['notes'],
                'created_at' => $r['created_at'],
            ];
        }, $rows);

        $this->json(['data' => $data]);
    }
}
