<?php

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Models\EventModel;

class EventsApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireScope('events:read');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $sportId = isset($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
        $type    = $_GET['type'] ?? '';
        $from    = $_GET['from'] ?? null;
        $result  = (new EventModel())->listForClub($sportId, $type ?: null, $from, $page, 50);
        $this->paginated($result);
    }

    public function upcoming(): void
    {
        $this->requireScope('events:read');
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $events = (new EventModel())->upcomingForClub($limit);
        $this->json(['data' => $events]);
    }

    /**
     * POST /api/v1/events/:id/attendance — zawodnik oznacza obecność.
     * Dla event.type='trening' używa tabeli `trainings`/`training_attendees`
     * gdy istnieje powiązany trening, w przeciwnym razie pada na `event_entries`.
     */
    public function attendance(string $id): void
    {
        $this->requireMember();

        $eventId = (int)$id;
        $input   = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $status  = (string)($input['status'] ?? '');
        $allowed = ['obecny','nieobecny','spozniony'];
        if (!in_array($status, $allowed, true)) {
            $this->error('Status musi być: obecny|nieobecny|spozniony.', 400, 'invalid_status');
        }

        $db = Database::pdo();
        $stmt = $db->prepare("SELECT id, type FROM events WHERE id = ? AND club_id = ? LIMIT 1");
        $stmt->execute([$eventId, $this->clubId]);
        $event = $stmt->fetch();
        if (!$event) {
            $this->error('Wydarzenie nie istnieje.', 404, 'event_not_found');
        }

        if ($event['type'] === 'trening') {
            // Spróbuj zmapować event → training po nazwie/dacie nie jest pewne —
            // używamy tabeli training_attendees bezpośrednio przez training_id = event.id
            // tylko gdy istnieje. Inaczej fallback do event_entries jako rezerwa.
            $tcheck = $db->prepare("SELECT id FROM trainings WHERE id = ? AND club_id = ? LIMIT 1");
            $tcheck->execute([$eventId, $this->clubId]);
            if ($tcheck->fetchColumn()) {
                $up = $db->prepare(
                    "INSERT INTO training_attendees (training_id, member_id, status, registered_at)
                     VALUES (?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE status = VALUES(status)"
                );
                $up->execute([$eventId, $this->memberId, $status]);
                $this->json(['status' => 'ok', 'source' => 'training_attendees']);
            }
        }

        // event_entries.status enum: zgloszony|potwierdzony|wycofany|dyskwalifikowany
        // Mobile statusy mapujemy: obecny→potwierdzony, nieobecny→wycofany, spozniony→potwierdzony.
        $entryStatus = match ($status) {
            'obecny'    => 'potwierdzony',
            'nieobecny' => 'wycofany',
            'spozniony' => 'potwierdzony',
        };
        $up = $db->prepare(
            "INSERT INTO event_entries (event_id, member_id, status, registered_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status)"
        );
        $up->execute([$eventId, $this->memberId, $entryStatus]);
        $this->json(['status' => 'ok', 'source' => 'event_entries', 'entry_status' => $entryStatus]);
    }
}
