<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\IcsGenerator;

class IcsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /**
     * Download .ics for an event.
     */
    public function event(string $id): void
    {
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND club_id = ?");
        $stmt->execute([(int)$id, $this->currentClub()]);
        $event = $stmt->fetch();

        if (!$event) {
            http_response_code(404);
            echo 'Nie znaleziono wydarzenia.';
            exit;
        }

        IcsGenerator::download([
            'uid'         => 'event-' . $event['id'] . '@klubsportowy',
            'summary'     => $event['name'],
            'dtstart'     => $event['event_date'],
            'dtend'       => $event['end_date'] ?? $event['event_date'],
            'location'    => $event['location'] ?? '',
            'description' => $event['description'] ?? '',
        ], 'wydarzenie-' . (int)$event['id'] . '.ics');
    }

    /**
     * Download .ics for a training session.
     */
    public function training(string $id): void
    {
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM trainings WHERE id = ? AND club_id = ?");
        $stmt->execute([(int)$id, $this->currentClub()]);
        $training = $stmt->fetch();

        if (!$training) {
            http_response_code(404);
            echo 'Nie znaleziono treningu.';
            exit;
        }

        IcsGenerator::download([
            'uid'         => 'training-' . $training['id'] . '@klubsportowy',
            'summary'     => $training['name'],
            'dtstart'     => $training['start_time'],
            'dtend'       => $training['end_time'] ?? $training['start_time'],
            'location'    => $training['location'] ?? '',
            'description' => $training['description'] ?? '',
        ], 'trening-' . (int)$training['id'] . '.ics');
    }
}
