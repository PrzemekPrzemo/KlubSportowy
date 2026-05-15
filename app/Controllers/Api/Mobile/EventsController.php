<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;

/**
 * Mobile API v1 — events (tournaments, club events).
 * Reuses `events` table directly to avoid a hard dep on EventModel scoping.
 */
class EventsController extends V1Controller
{
    /** GET /api/mobile/v1/events?from=YYYY-MM-DD&to=YYYY-MM-DD */
    public function index(): void
    {
        $this->requireAuth();
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d', strtotime('+90 days'));

        $stmt = Database::pdo()->prepare(
            "SELECT e.id, e.name, e.type, e.location, e.event_date, e.end_date,
                    e.status, e.description
             FROM events e
             WHERE e.club_id = ?
               AND (DATE(e.event_date) BETWEEN ? AND ?
                    OR (e.end_date IS NOT NULL AND DATE(e.end_date) BETWEEN ? AND ?))
             ORDER BY e.event_date ASC"
        );
        $stmt->execute([$this->clubId, $from, $to, $from, $to]);
        $this->json($stmt->fetchAll());
    }

    /** GET /api/mobile/v1/events/:id */
    public function show(string $id): void
    {
        $this->requireAuth();
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM events WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([(int)$id, $this->clubId]);
        $row = $stmt->fetch();
        if (!$row) $this->error('Wydarzenie nie istnieje.', 404, 'not_found');
        $this->json($row);
    }

    /**
     * POST /api/mobile/v1/events/:id/register — stub.
     * Real registration depends on event-type-specific fields (discipline, team, etc.);
     * returns 501 with hint until that flow is wired up.
     */
    public function register(string $id): void
    {
        $this->requireAuth();
        $this->error(
            'Rejestracja na wydarzenia będzie dostępna w kolejnej wersji.',
            501,
            'not_implemented'
        );
    }
}
