<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use App\Models\BookableResourceModel;
use App\Models\BookingModel;
use App\Models\FacilityBookingModel;
use App\Models\FacilityModel;
use App\Models\MemberModel;

/**
 * Booking system — rezerwacje zasobow klubu.
 *
 * Wspiera dwa modele rownolegle:
 *   - NOWY (bookable_resources + bookings) — FullCalendar.js, conflict detection,
 *     recurring, requires_approval, multi-tenant przez ClubScopedModel.
 *   - LEGACY (facilities + facility_bookings) — zachowujemy dla zgodnosci wstecz
 *     pod /bookings/facilities/*.
 */
class BookingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /* ─────────────────────────────────────────────────────────────
     *  NEW booking system (bookable_resources + bookings)
     * ───────────────────────────────────────────────────────────── */

    /**
     * GET /bookings  → kalendarz FullCalendar.js (default view).
     */
    public function index(): void
    {
        $this->calendar();
    }

    /**
     * GET /bookings/calendar — FullCalendar.js z CDN.
     */
    public function calendar(): void
    {
        $resources = (new BookableResourceModel())->listActive();
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;
        $this->render('bookings/calendar', [
            'title'      => 'Kalendarz rezerwacji',
            'resources'  => $resources,
            'resourceId' => $resourceId,
        ]);
    }

    /**
     * GET /bookings/list — tabular lista bookings (admin).
     */
    public function list(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? null;
        $pagination = (new BookingModel())->listPaginated($page, 25, $status ?: null);
        $resources  = (new BookableResourceModel())->listAll();
        $this->render('bookings/list', [
            'title'      => 'Lista rezerwacji',
            'pagination' => $pagination,
            'resources'  => $resources,
            'status'     => $status,
        ]);
    }

    /**
     * GET /bookings/create?resource_id=X&start=Y — formularz.
     */
    public function create(): void
    {
        $resources = (new BookableResourceModel())->listActive();
        if (empty($resources)) {
            Session::flash('warning', 'Brak aktywnych zasobów. Najpierw dodaj zasób.');
            $this->redirect('club/resources');
        }
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : (int)$resources[0]['id'];
        $start      = $_GET['start']  ?? '';
        $end        = $_GET['end']    ?? '';
        $members    = (new MemberModel())->findAll('last_name');
        $this->render('bookings/form', [
            'title'      => 'Nowa rezerwacja',
            'resources'  => $resources,
            'resourceId' => $resourceId,
            'start'      => $start,
            'end'        => $end,
            'members'    => $members,
        ]);
    }

    /**
     * POST /bookings/store — utworzenie z conflict detection + walidacja okna.
     */
    public function store(): void
    {
        Csrf::verify();
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $start      = trim($_POST['start_at'] ?? '');
        $end        = trim($_POST['end_at']   ?? '');
        $title      = trim($_POST['title']    ?? '');
        $memberId   = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
        $notes      = trim($_POST['notes']    ?? '') ?: null;
        $recurringPattern = trim($_POST['recurring_pattern'] ?? '') ?: null;
        $recurringUntil   = trim($_POST['recurring_until']   ?? '') ?: null;

        if ($resourceId === 0 || $start === '' || $end === '' || $title === '') {
            Session::flash('error', 'Wszystkie pola obowiązkowe muszą być wypełnione.');
            $this->redirect('bookings/create?resource_id=' . $resourceId);
        }

        $start = str_replace('T', ' ', $start);
        $end   = str_replace('T', ' ', $end);

        $resource = (new BookableResourceModel())->findById($resourceId);
        if (!$resource) {
            Session::flash('error', 'Wybrany zasób nie istnieje w tym klubie.');
            $this->redirect('bookings');
        }

        $err = $this->validateAgainstResource($resource, $start, $end);
        if ($err !== null) {
            Session::flash('error', $err);
            $this->redirect('bookings/create?resource_id=' . $resourceId);
        }

        $model = new BookingModel();
        if (!$model->isAvailable($resourceId, $start, $end)) {
            Session::flash('error', 'Termin zajęty — wybierz inny.');
            $this->redirect('bookings/create?resource_id=' . $resourceId);
        }

        $status = !empty($resource['requires_approval']) ? 'pending' : 'confirmed';

        $parentId = $model->insert([
            'resource_id'        => $resourceId,
            'member_id'          => $memberId,
            'booked_by_user_id'  => Auth::id(),
            'title'              => $title,
            'description'        => trim($_POST['description'] ?? '') ?: null,
            'start_at'           => $start,
            'end_at'             => $end,
            'participants_count' => !empty($_POST['participants_count']) ? (int)$_POST['participants_count'] : null,
            'status'             => $status,
            'recurring_pattern'  => $recurringPattern,
            'recurring_until'    => $recurringUntil ?: null,
            'notes'              => $notes,
        ]);

        // Recurring — generuj kolejne wpisy (best-effort, prosty RRULE: FREQ=WEEKLY;BYDAY=...).
        if ($recurringPattern && $recurringUntil) {
            $this->generateRecurring($parentId, $resourceId, $memberId, $title, $start, $end,
                                     $recurringPattern, $recurringUntil, $status, $notes);
        }

        // Notification (best-effort)
        $this->notifyBookingCreated($parentId, $memberId);

        Session::flash('success', 'Rezerwacja utworzona' . ($status === 'pending' ? ' — czeka na akceptację.' : '.'));
        $this->redirect('bookings/' . $parentId);
    }

    /**
     * GET /bookings/:id — detail.
     */
    public function show(string $id): void
    {
        $booking = (new BookingModel())->findWithJoins((int)$id);
        if (!$booking) {
            Session::flash('error', 'Rezerwacja nie istnieje.');
            $this->redirect('bookings');
        }
        $this->render('bookings/show', [
            'title'   => 'Rezerwacja #' . (int)$id,
            'booking' => $booking,
        ]);
    }

    /**
     * POST /bookings/:id/cancel — anulowanie (admin lub bookujacy).
     */
    public function cancel(string $id): void
    {
        Csrf::verify();
        $model = new BookingModel();
        $booking = $model->findById((int)$id);
        if (!$booking) {
            Session::flash('error', 'Rezerwacja nie istnieje.');
            $this->redirect('bookings');
        }
        $reason = trim($_POST['cancellation_reason'] ?? '') ?: null;

        // Admin moze anulowac cudze — log do activity_log.
        if (!empty($booking['booked_by_user_id']) && (int)$booking['booked_by_user_id'] !== (int)Auth::id()
            && Auth::hasRole(['zarzad','admin','trener'])) {
            try {
                (new ActivityLogModel())->log(
                    'booking.cancel.admin',
                    'booking',
                    (int)$id,
                    json_encode(['reason' => $reason], JSON_UNESCAPED_UNICODE) ?: null
                );
            } catch (\Throwable) {}
        }

        $model->update((int)$id, [
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at'        => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Rezerwacja anulowana.');
        $this->redirect('bookings');
    }

    /**
     * POST /bookings/:id/confirm — admin approve gdy requires_approval.
     */
    public function confirm(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['zarzad','admin','trener']);
        $model = new BookingModel();
        $booking = $model->findById((int)$id);
        if (!$booking) {
            Session::flash('error', 'Rezerwacja nie istnieje.');
            $this->redirect('bookings');
        }
        $model->update((int)$id, ['status' => 'confirmed']);
        Session::flash('success', 'Rezerwacja potwierdzona.');
        $this->redirect('bookings/' . (int)$id);
    }

    /**
     * GET /bookings/api/events?from=&to=&resource_id= — JSON feed dla FullCalendar.
     */
    public function apiEvents(): void
    {
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d', strtotime($from . ' +30 days'));
        $resourceId = isset($_GET['resource_id']) && $_GET['resource_id'] !== ''
                       ? (int)$_GET['resource_id'] : null;

        // FullCalendar przesyla daty jako YYYY-MM-DD lub ISO z T — normalizujemy.
        $from = str_replace('T', ' ', $from);
        $to   = str_replace('T', ' ', $to);
        // Doloz czas gdy tylko data:
        if (!str_contains($from, ' ')) $from .= ' 00:00:00';
        if (!str_contains($to,   ' ')) $to   .= ' 23:59:59';

        $events = (new BookingModel())->forCalendar($from, $to, $resourceId);
        $this->json($events);
    }

    /* ─────────────────────────────────────────────────────────────
     *  Helpers
     * ───────────────────────────────────────────────────────────── */

    private function validateAgainstResource(array $resource, string $start, string $end): ?string
    {
        $startTs = strtotime($start);
        $endTs   = strtotime($end);
        if (!$startTs || !$endTs || $endTs <= $startTs) {
            return 'Niepoprawny zakres dat.';
        }

        $nowTs = time();
        $diffH = ($startTs - $nowTs) / 3600.0;
        if ($diffH < (int)$resource['min_advance_hours']) {
            return 'Najwcześniej można zarezerwować na ' . (int)$resource['min_advance_hours'] . ' godz wprzód.';
        }
        $diffD = ($startTs - $nowTs) / 86400.0;
        if ($diffD > (int)$resource['max_advance_days']) {
            return 'Maksymalnie ' . (int)$resource['max_advance_days'] . ' dni wprzód.';
        }

        if (!empty($resource['max_duration_minutes'])) {
            $minutes = ($endTs - $startTs) / 60;
            if ($minutes > (int)$resource['max_duration_minutes']) {
                return 'Maks. czas rezerwacji: ' . (int)$resource['max_duration_minutes'] . ' min.';
            }
        }

        // Weekday check (1=Mon..7=Sun).
        $isoDow = (int)date('N', $startTs);
        $allowed = array_map('intval', array_filter(explode(',', $resource['available_weekdays'] ?? '1,2,3,4,5,6,7')));
        if (!empty($allowed) && !in_array($isoDow, $allowed, true)) {
            return 'Zasób niedostępny w tym dniu tygodnia.';
        }

        // Godziny otwarcia.
        if (!empty($resource['available_from']) && !empty($resource['available_until'])) {
            $hStart = date('H:i:s', $startTs);
            $hEnd   = date('H:i:s', $endTs);
            if ($hStart < $resource['available_from'] || $hEnd > $resource['available_until']) {
                return 'Zasób otwarty ' . substr($resource['available_from'], 0, 5)
                     . ' — ' . substr($resource['available_until'], 0, 5) . '.';
            }
        }

        return null;
    }

    /**
     * Prosty RRULE parser: FREQ=WEEKLY;BYDAY=MO,WE,FR.
     * Generuje wpisy o tej samej godzinie, az do recurring_until.
     */
    private function generateRecurring(int $parentId, int $resourceId, ?int $memberId, string $title,
                                       string $start, string $end, string $pattern, string $until,
                                       string $status, ?string $notes): void
    {
        if (!preg_match('/FREQ=WEEKLY/i', $pattern)) return;
        $byday = [];
        if (preg_match('/BYDAY=([A-Z,]+)/i', $pattern, $m)) {
            $map = ['MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6,'SU'=>7];
            foreach (explode(',', strtoupper($m[1])) as $d) {
                if (isset($map[$d])) $byday[] = $map[$d];
            }
        }
        if (empty($byday)) return;

        $durationSec = strtotime($end) - strtotime($start);
        $untilTs = strtotime($until . ' 23:59:59');
        if (!$untilTs) return;

        $model = new BookingModel();
        $cursor = strtotime($start) + 86400; // od nastepnego dnia
        $safety = 0;
        while ($cursor <= $untilTs && $safety < 200) {
            $safety++;
            $dow = (int)date('N', $cursor);
            if (in_array($dow, $byday, true)) {
                $occStart = date('Y-m-d H:i:s', $cursor);
                $occEnd   = date('Y-m-d H:i:s', $cursor + $durationSec);
                if ($model->isAvailable($resourceId, $occStart, $occEnd)) {
                    $model->insert([
                        'resource_id'       => $resourceId,
                        'member_id'         => $memberId,
                        'booked_by_user_id' => Auth::id(),
                        'title'             => $title,
                        'start_at'          => $occStart,
                        'end_at'            => $occEnd,
                        'status'            => $status,
                        'parent_booking_id' => $parentId,
                        'notes'             => $notes,
                    ]);
                }
            }
            $cursor += 86400;
        }
    }

    private function notifyBookingCreated(int $bookingId, ?int $memberId): void
    {
        // Best-effort: in-app notification + email if available.
        try {
            if ($memberId && class_exists(\App\Models\MemberNotificationModel::class)) {
                $clubId = ClubContext::current();
                if ($clubId !== null) {
                    (new \App\Models\MemberNotificationModel())->notify(
                        $memberId,
                        (int)$clubId,
                        'booking',
                        'Nowa rezerwacja',
                        'Twoja rezerwacja #' . $bookingId . ' została utworzona.',
                        url('portal/bookings')
                    );
                }
            }
        } catch (\Throwable) {}
    }

    /* ─────────────────────────────────────────────────────────────
     *  LEGACY: facilities (facility_bookings) — zachowane.
     * ───────────────────────────────────────────────────────────── */

    public function facilities(): void
    {
        $list = (new FacilityModel())->listForClub();
        $this->render('bookings/facilities', [
            'title'      => 'Obiekty sportowe (legacy)',
            'facilities' => $list,
        ]);
    }

    public function storeFacility(): void
    {
        Csrf::verify();
        $name     = trim($_POST['name'] ?? '');
        $type     = in_array($_POST['type'] ?? '', ['boisko','sala','hala','tor','strzelnica','basen','kort','inne'], true)
            ? $_POST['type'] : 'inne';
        $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;
        $location = trim($_POST['location'] ?? '') ?: null;
        $desc     = trim($_POST['description'] ?? '') ?: null;

        if ($name === '') {
            Session::flash('error', 'Nazwa obiektu jest wymagana.');
            $this->redirect('bookings/facilities');
        }

        (new FacilityModel())->insert([
            'name'        => $name,
            'type'        => $type,
            'capacity'    => $capacity,
            'location'    => $location,
            'description' => $desc,
        ]);

        Session::flash('success', 'Obiekt dodany.');
        $this->redirect('bookings/facilities');
    }

    public function deleteFacility(string $id): void
    {
        Csrf::verify();
        (new FacilityModel())->delete((int)$id);
        Session::flash('success', 'Obiekt usunięty.');
        $this->redirect('bookings/facilities');
    }

    public function legacyCalendar(): void
    {
        $facilityId = isset($_GET['facility']) ? (int)$_GET['facility'] : null;
        $weekStart  = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));

        $facilities = (new FacilityModel())->listForClub();
        if ($facilityId === null && !empty($facilities)) {
            $facilityId = (int)$facilities[0]['id'];
        }

        $weekEnd = date('Y-m-d 23:59:59', strtotime($weekStart . ' +6 days'));
        $bookings = [];
        if ($facilityId) {
            $bookings = (new FacilityBookingModel())->forFacility(
                $facilityId,
                $weekStart . ' 00:00:00',
                $weekEnd
            );
        }

        $members = (new MemberModel())->findAll('last_name');

        $this->render('bookings/calendar', [
            'title'      => 'Kalendarz rezerwacji (legacy)',
            'facilities' => $facilities,
            'facilityId' => $facilityId,
            'weekStart'  => $weekStart,
            'bookings'   => $bookings,
            'members'    => $members,
        ]);
    }

    public function legacyBook(): void
    {
        Csrf::verify();
        $facilityId  = (int)($_POST['facility_id'] ?? 0);
        $startTime   = trim($_POST['start_time'] ?? '');
        $endTime     = trim($_POST['end_time'] ?? '');
        $title       = trim($_POST['title'] ?? '');
        $notes       = trim($_POST['notes'] ?? '') ?: null;
        $bookedForId = !empty($_POST['booked_for_id']) ? (int)$_POST['booked_for_id'] : null;

        if ($facilityId === 0 || $startTime === '' || $endTime === '' || $title === '') {
            Session::flash('error', 'Wszystkie pola są wymagane.');
            $this->redirect('bookings');
        }

        $startTime = str_replace('T', ' ', $startTime);
        $endTime   = str_replace('T', ' ', $endTime);

        $conflicts = (new FacilityBookingModel())->conflicts($facilityId, $startTime, $endTime);
        if (!empty($conflicts)) {
            Session::flash('error', 'Termin koliduje z istniejącą rezerwacją.');
            $this->redirect('bookings');
        }

        (new FacilityBookingModel())->insert([
            'facility_id'   => $facilityId,
            'booked_by'     => Auth::id(),
            'booked_for_id' => $bookedForId,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'title'         => $title,
            'notes'         => $notes,
            'status'        => 'confirmed',
        ]);

        Session::flash('success', 'Rezerwacja utworzona.');
        $this->redirect('bookings/legacy-calendar?facility=' . $facilityId);
    }
}
