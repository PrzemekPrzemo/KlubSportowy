<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\NotificationDispatcher;
use App\Helpers\Ranking\RankingEngine;
use App\Helpers\Session;
use App\Models\EventModel;
use App\Models\SportModel;
use PDO;

class EventsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $sport = isset($_GET['sport']) ? (int)$_GET['sport'] : null;
        $type  = $_GET['type'] ?? '';
        $page  = max(1, (int)($_GET['page'] ?? 1));

        $pagination = (new EventModel())->listForClub($sport, $type ?: null, null, $page, 20);
        $sports     = (new SportModel())->listForClub($this->currentClub());

        $this->render('events/index', [
            'title'       => 'Wydarzenia',
            'pagination'  => $pagination,
            'sportFilter' => $sport,
            'typeFilter'  => $type,
            'sports'      => $sports,
        ]);
    }

    public function create(): void
    {
        $sports = (new SportModel())->listForClub($this->currentClub());
        $this->render('events/form', [
            'title'  => 'Nowe wydarzenie',
            'event'  => null,
            'sports' => $sports,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'sport_id'   => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'type'       => in_array($_POST['type'] ?? '', ['mecz','zawody','trening','obóz','turniej','inny'], true)
                            ? $_POST['type'] : 'zawody',
            'name'       => trim($_POST['name'] ?? ''),
            'event_date' => trim($_POST['event_date'] ?? ''),
            'end_date'   => trim($_POST['end_date'] ?? '') ?: null,
            'location'   => trim($_POST['location'] ?? '') ?: null,
            'status'     => 'planowane',
            'description'=> trim($_POST['description'] ?? '') ?: null,
            'created_by' => Auth::id(),
        ];

        if ($data['name'] === '' || $data['event_date'] === '') {
            Session::flash('error', 'Nazwa i data są wymagane.');
            $this->redirect('events/create');
        }

        (new EventModel())->insert($data);

        // Notify club members about future events
        if (!empty($data['event_date']) && strtotime($data['event_date']) > time()) {
            $clubId = ClubContext::current();
            if ($clubId) {
                NotificationDispatcher::notifyClubMembers($clubId, 'new_event', [
                    'event_name' => $data['name'],
                    'event_date' => $data['event_date'],
                    'event_location' => $data['location'] ?? '',
                    'event_type' => $data['type'],
                ]);
            }
        }

        Session::flash('success', 'Wydarzenie dodane.');
        $this->redirect('events');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new EventModel())->delete((int)$id);
        Session::flash('success', 'Wydarzenie usunięte.');
        $this->redirect('events');
    }

    /**
     * GET /events/:id/results — formularz wpisywania wyników eventu.
     */
    public function recordResults(string $id): void
    {
        $this->requireRole(['zarzad', 'trener', 'admin', 'sedzia']);

        $eventId = (int)$id;
        $db = Database::pdo();
        $clubId = ClubContext::current();

        $stmt = $db->prepare(
            "SELECT e.*, s.name AS sport_name, s.`key` AS sport_key
               FROM events e
          LEFT JOIN sports s ON s.id = e.sport_id
              WHERE e.id = ? AND e.club_id = ? LIMIT 1"
        );
        $stmt->execute([$eventId, $clubId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            Session::flash('error', 'Wydarzenie nie istnieje.');
            $this->redirect('events');
        }

        // Zapisani uczestnicy + już wpisane wyniki.
        $rows = $db->prepare(
            "SELECT ee.member_id, m.first_name, m.last_name, m.member_number,
                    er.score, er.place, er.extra, er.id AS result_id
               FROM event_entries ee
               JOIN members m ON m.id = ee.member_id
          LEFT JOIN event_results er ON er.event_id = ee.event_id AND er.member_id = ee.member_id
              WHERE ee.event_id = ?
           ORDER BY er.place ASC, m.last_name ASC"
        );
        $rows->execute([$eventId]);
        $entries = $rows->fetchAll(PDO::FETCH_ASSOC);

        $this->render('events/record_results', [
            'title'   => 'Wyniki: ' . $event['name'],
            'event'   => $event,
            'entries' => $entries,
        ]);
    }

    /**
     * POST /events/:id/results/save — zapis + recalc rankingu eventu.
     */
    public function saveResults(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['zarzad', 'trener', 'admin', 'sedzia']);

        $eventId = (int)$id;
        $db = Database::pdo();
        $clubId = ClubContext::current();

        $check = $db->prepare("SELECT id FROM events WHERE id = ? AND club_id = ? LIMIT 1");
        $check->execute([$eventId, $clubId]);
        if (!$check->fetchColumn()) {
            Session::flash('error', 'Wydarzenie nie istnieje.');
            $this->redirect('events');
        }

        $rows = $_POST['results'] ?? [];
        $saved = 0;
        if (is_array($rows)) {
            foreach ($rows as $memberId => $row) {
                $memberId = (int)$memberId;
                if ($memberId <= 0 || !is_array($row)) continue;

                $score = isset($row['score']) && $row['score'] !== '' ? (float)$row['score'] : null;
                $place = isset($row['place']) && $row['place'] !== '' ? (int)$row['place'] : null;
                $time  = isset($row['time'])  && $row['time']  !== '' ? (float)$row['time'] : null;
                $notes = isset($row['notes']) ? trim((string)$row['notes']) : '';

                if ($score === null && $place === null && $time === null && $notes === '') continue;

                $extra = [];
                if ($time !== null) $extra['time'] = $time;
                $extraJson = $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null;

                // Idempotency via DELETE + INSERT (event_results lacks UNIQUE on (event_id, member_id)).
                $del = $db->prepare("DELETE FROM event_results WHERE event_id = ? AND member_id = ?");
                $del->execute([$eventId, $memberId]);

                $ins = $db->prepare(
                    "INSERT INTO event_results
                        (event_id, member_id, score, place, extra, notes, entered_by, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                $ins->execute([
                    $eventId,
                    $memberId,
                    $score,
                    $place,
                    $extraJson,
                    $notes !== '' ? $notes : null,
                    Auth::id(),
                ]);
                $saved++;
            }
        }

        try {
            $result = RankingEngine::recalculateForEvent($eventId);
            $recalc = count($result);
        } catch (\Throwable $e) {
            error_log("RankingEngine::recalculateForEvent({$eventId}) failed: " . $e->getMessage());
            $recalc = 0;
        }

        Session::flash('success', "Zapisano {$saved} wyników. Ranking przeliczony dla {$recalc} członków.");
        $this->redirect('events/' . $eventId . '/results');
    }
}
