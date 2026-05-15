<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;

/**
 * Mobile API v1 — trainings and RSVP.
 * Reads from `trainings` + `training_attendees`. Re-uses club scoping by member.
 */
class TrainingsController extends V1Controller
{
    /** GET /api/mobile/v1/trainings?from=YYYY-MM-DD&to=YYYY-MM-DD */
    public function index(): void
    {
        $this->requireAuth();
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
        $to   = $_GET['to']   ?? date('Y-m-d', strtotime('+30 days'));

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT t.id, t.name, t.description, t.location, t.start_time, t.end_time,
                    t.status, t.max_participants,
                    s.name AS sport_name, s.color AS sport_color,
                    u.full_name AS instructor_name,
                    (SELECT COUNT(*) FROM training_attendees
                     WHERE training_id = t.id AND status IN ('zapisany','obecny')) AS attendees_count,
                    ta.status AS my_status
             FROM trainings t
             LEFT JOIN sports s ON s.id = t.sport_id
             LEFT JOIN users u  ON u.id = t.instructor_id
             LEFT JOIN training_attendees ta
                 ON ta.training_id = t.id AND ta.member_id = ?
             WHERE t.club_id = ?
               AND DATE(t.start_time) BETWEEN ? AND ?
             ORDER BY t.start_time ASC"
        );
        $stmt->execute([$this->memberId, $this->clubId, $from, $to]);
        $rows = $stmt->fetchAll();

        $this->json(array_map([$this, 'shapeTraining'], $rows));
    }

    /** GET /api/mobile/v1/trainings/:id */
    public function show(string $id): void
    {
        $this->requireAuth();
        $tid = (int)$id;
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT t.*, s.name AS sport_name, s.color AS sport_color,
                    u.full_name AS instructor_name,
                    ta.status AS my_status
             FROM trainings t
             LEFT JOIN sports s ON s.id = t.sport_id
             LEFT JOIN users u  ON u.id = t.instructor_id
             LEFT JOIN training_attendees ta
                 ON ta.training_id = t.id AND ta.member_id = ?
             WHERE t.id = ? AND t.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$this->memberId, $tid, $this->clubId]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->error('Trening nie istnieje.', 404, 'not_found');
        }

        $att = $db->prepare(
            "SELECT ta.status, m.first_name, m.last_name, m.member_number
             FROM training_attendees ta
             JOIN members m ON m.id = ta.member_id
             WHERE ta.training_id = ?
             ORDER BY m.last_name, m.first_name"
        );
        $att->execute([$tid]);

        $data = $this->shapeTraining($row);
        $data['description'] = $row['description'] ?? null;
        $data['attendees']   = $att->fetchAll();
        $this->json($data);
    }

    /**
     * POST /api/mobile/v1/trainings/:id/rsvp
     * Body: { status: confirmed | tentative | declined }
     */
    public function rsvp(string $id): void
    {
        $this->requireAuth();
        $tid = (int)$id;
        $input = $this->input();
        $raw = strtolower(trim((string)($input['status'] ?? '')));

        // Map mobile-friendly statuses → DB enum.
        $map = [
            'confirmed' => 'zapisany',
            'tentative' => 'zapisany',
            'declined'  => 'wypisany',
            'zapisany'  => 'zapisany',
            'wypisany'  => 'wypisany',
        ];
        if (!isset($map[$raw])) {
            $this->error('Nieprawidłowy status. Dozwolone: confirmed, tentative, declined.', 422, 'validation');
        }
        $dbStatus = $map[$raw];

        $db = Database::pdo();
        // Confirm training belongs to member's club.
        $check = $db->prepare("SELECT id FROM trainings WHERE id = ? AND club_id = ? LIMIT 1");
        $check->execute([$tid, $this->clubId]);
        if (!$check->fetchColumn()) {
            $this->error('Trening nie istnieje.', 404, 'not_found');
        }

        $stmt = $db->prepare(
            "INSERT INTO training_attendees (training_id, member_id, status)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)"
        );
        $stmt->execute([$tid, $this->memberId, $dbStatus]);

        $this->json(['training_id' => $tid, 'my_status' => $dbStatus]);
    }

    private function shapeTraining(array $r): array
    {
        return [
            'id'               => (int)$r['id'],
            'name'             => $r['name'],
            'location'         => $r['location'] ?? null,
            'start_time'       => $r['start_time'],
            'end_time'         => $r['end_time'] ?? null,
            'status'           => $r['status'] ?? null,
            'max_participants' => isset($r['max_participants']) ? (int)$r['max_participants'] : null,
            'attendees_count'  => isset($r['attendees_count']) ? (int)$r['attendees_count'] : null,
            'sport_name'       => $r['sport_name'] ?? null,
            'sport_color'      => $r['sport_color'] ?? null,
            'instructor_name'  => $r['instructor_name'] ?? null,
            'my_status'        => $r['my_status'] ?? null,
        ];
    }
}
