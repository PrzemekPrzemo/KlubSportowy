<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\IcsGenerator;
use App\Helpers\Session;
use App\Models\CalendarEventCategoryModel;
use App\Models\CalendarEventModel;
use App\Models\ClubSettingsModel;
use App\Models\SportModel;

class CalendarController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $events     = (new CalendarEventModel())->listForMonth($year, $month);
        $categories = (new CalendarEventCategoryModel())->findAll('name');
        $upcoming   = (new CalendarEventModel())->listUpcoming(30);

        // Ensure default categories exist for this club
        if (empty($categories)) {
            (new CalendarEventCategoryModel())->seedDefaults($this->currentClub());
            $categories = (new CalendarEventCategoryModel())->findAll('name');
        }

        $this->render('calendar/index', [
            'title'      => 'Kalendarz',
            'year'       => $year,
            'month'      => $month,
            'events'     => $events,
            'categories' => $categories,
            'upcoming'   => $upcoming,
        ]);
    }

    public function create(): void
    {
        $categories = (new CalendarEventCategoryModel())->findAll('name');
        $sports     = (new SportModel())->listForClub($this->currentClub());
        if (empty($categories)) {
            (new CalendarEventCategoryModel())->seedDefaults($this->currentClub());
            $categories = (new CalendarEventCategoryModel())->findAll('name');
        }
        $this->render('calendar/form', [
            'title'      => 'Nowy wpis w kalendarzu',
            'event'      => null,
            'categories' => $categories,
            'sports'     => $sports,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['created_by'] = Auth::id();
        (new CalendarEventModel())->insert($data);
        Session::flash('success', 'Wpis dodany do kalendarza.');
        $this->redirect('calendar');
    }

    public function edit(string $id): void
    {
        $event = (new CalendarEventModel())->findById((int)$id);
        if (!$event) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect('calendar');
        }
        $categories = (new CalendarEventCategoryModel())->findAll('name');
        $sports     = (new SportModel())->listForClub($this->currentClub());
        $this->render('calendar/form', [
            'title'      => 'Edycja wpisu',
            'event'      => $event,
            'categories' => $categories,
            'sports'     => $sports,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new CalendarEventModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('calendar');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new CalendarEventModel())->delete((int)$id);
        Session::flash('success', 'Wpis usunięty.');
        $this->redirect('calendar');
    }

    /**
     * Public iCal feed: GET /cal/{token}
     * No auth required — token acts as read-only secret.
     */
    public function calendarFeed(string $token): void
    {
        try {
            $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
            if (strlen($token) < 32) {
                http_response_code(403);
                echo 'Invalid token.';
                exit;
            }

            $db   = Database::pdo();
            $stmt = $db->prepare(
                "SELECT club_id FROM club_settings WHERE `key` = 'ical_token' AND value = ? LIMIT 1"
            );
            $stmt->execute([$token]);
            $row = $stmt->fetch();

            if (!$row) {
                http_response_code(403);
                echo 'Token not found.';
                exit;
            }

            $clubId = (int)$row['club_id'];

            // Fetch club name
            $clubStmt = $db->prepare("SELECT name FROM clubs WHERE id = ?");
            $clubStmt->execute([$clubId]);
            $clubName = $clubStmt->fetchColumn() ?: 'Klub';

            // Fetch upcoming events (next 365 days) + past 30 days
            $evStmt = $db->prepare(
                "SELECT ce.*, cec.name AS category_name
                 FROM calendar_events ce
                 LEFT JOIN calendar_event_categories cec ON cec.id = ce.category_id
                 WHERE ce.club_id = ?
                   AND ce.visibility IN ('public', 'club')
                   AND ce.start_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   AND ce.start_at <= DATE_ADD(NOW(), INTERVAL 365 DAY)
                 ORDER BY ce.start_at ASC
                 LIMIT 500"
            );
            $evStmt->execute([$clubId]);
            $events = $evStmt->fetchAll();

            $lines = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//KlubSportowy//' . $clubName . '//PL',
                'CALSCALE:GREGORIAN',
                'METHOD:PUBLISH',
                'X-WR-CALNAME:' . IcsGenerator::escapePublic($clubName),
                'X-WR-TIMEZONE:Europe/Warsaw',
            ];

            foreach ($events as $ev) {
                $uid     = 'cal-' . $clubId . '-' . $ev['id'] . '@klubsportowy';
                $summary = $ev['title'] ?? 'Wydarzenie';
                if (!empty($ev['category_name'])) {
                    $summary = '[' . $ev['category_name'] . '] ' . $summary;
                }

                $dtstart = self::toIcalDate($ev['start_at'] ?? '', (bool)($ev['all_day'] ?? false));
                $dtend   = !empty($ev['end_at'])
                    ? self::toIcalDate($ev['end_at'], (bool)($ev['all_day'] ?? false))
                    : $dtstart;

                $now = gmdate('Ymd\THis\Z');

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:' . $uid;
                $lines[] = 'DTSTAMP:' . $now;

                if ($ev['all_day'] ?? false) {
                    $lines[] = 'DTSTART;VALUE=DATE:' . $dtstart;
                    $lines[] = 'DTEND;VALUE=DATE:' . $dtend;
                } else {
                    $lines[] = 'DTSTART:' . $dtstart;
                    $lines[] = 'DTEND:' . $dtend;
                }

                $lines[] = 'SUMMARY:' . IcsGenerator::escapePublic($summary);
                if (!empty($ev['location'])) {
                    $lines[] = 'LOCATION:' . IcsGenerator::escapePublic($ev['location']);
                }
                if (!empty($ev['description'])) {
                    $lines[] = 'DESCRIPTION:' . IcsGenerator::escapePublic($ev['description']);
                }
                $lines[] = 'END:VEVENT';
            }

            $lines[] = 'END:VCALENDAR';
            $ics = implode("\r\n", $lines) . "\r\n";

            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: inline; filename="kalendarz.ics"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-Length: ' . strlen($ics));
            echo $ics;
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Calendar feed error.';
            exit;
        }
    }

    /**
     * Show iCal subscription URL for the current club (authenticated).
     */
    public function icalSubscription(): void
    {
        $clubId   = $this->currentClub();
        $settings = new ClubSettingsModel();
        $token    = $settings->get($clubId, 'ical_token', '');

        if ($token === '') {
            $token = bin2hex(random_bytes(24));
            $settings->set($clubId, 'ical_token', $token, 'text', 'iCal token');
        }

        $appCfg  = require ROOT_PATH . '/config/app.php';
        $baseUrl = rtrim($appCfg['base_url'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
        $url     = $baseUrl . '/cal/' . $token;

        $this->render('calendar/ical_subscription', [
            'title'    => 'Subskrypcja kalendarza (iCal)',
            'icalUrl'  => $url,
        ]);
    }

    private static function toIcalDate(string $datetime, bool $allDay): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) $ts = time();
        return $allDay ? gmdate('Ymd', $ts) : gmdate('Ymd\THis\Z', $ts);
    }

    private function parsePost(): ?array
    {
        $data = [
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'location'    => trim($_POST['location'] ?? '') ?: null,
            'start_at'    => trim($_POST['start_at'] ?? ''),
            'end_at'      => trim($_POST['end_at'] ?? '') ?: null,
            'all_day'     => isset($_POST['all_day']) ? 1 : 0,
            'visibility'  => in_array($_POST['visibility'] ?? '', ['private','club','public'], true) ? $_POST['visibility'] : 'club',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'sport_id'    => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
        ];
        if ($data['title'] === '' || $data['start_at'] === '') {
            Session::flash('error', 'Tytuł i data startu są wymagane.');
            $this->redirect('calendar/create');
            return null;
        }
        $data['start_at'] = str_replace('T', ' ', $data['start_at']);
        if ($data['end_at']) $data['end_at'] = str_replace('T', ' ', $data['end_at']);
        return $data;
    }
}
