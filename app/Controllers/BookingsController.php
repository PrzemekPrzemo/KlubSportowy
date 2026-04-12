<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\FacilityBookingModel;
use App\Models\FacilityModel;
use App\Models\MemberModel;

class BookingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /* ── Facilities ────────────────────────────────────────── */

    public function facilities(): void
    {
        $list = (new FacilityModel())->listForClub();
        $this->render('bookings/facilities', [
            'title'      => 'Obiekty sportowe',
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

    /* ── Calendar ──────────────────────────────────────────── */

    public function calendar(): void
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
            'title'      => 'Kalendarz rezerwacji',
            'facilities' => $facilities,
            'facilityId' => $facilityId,
            'weekStart'  => $weekStart,
            'bookings'   => $bookings,
            'members'    => $members,
        ]);
    }

    /* ── Book ──────────────────────────────────────────────── */

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new FacilityBookingModel())->myBookings((int)Auth::id(), $page);
        $facilities = (new FacilityModel())->listForClub();
        $members    = (new MemberModel())->findAll('last_name');

        $this->render('bookings/book_form', [
            'title'      => 'Rezerwacje',
            'pagination' => $pagination,
            'facilities' => $facilities,
            'members'    => $members,
        ]);
    }

    public function book(): void
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

        // Sprawdź konflikty
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
        $this->redirect('bookings/calendar?facility=' . $facilityId);
    }

    public function cancel(string $id): void
    {
        Csrf::verify();
        $model   = new FacilityBookingModel();
        $booking = $model->findById((int)$id);
        if (!$booking) {
            Session::flash('error', 'Rezerwacja nie istnieje.');
            $this->redirect('bookings');
        }

        $model->update((int)$id, ['status' => 'cancelled']);
        Session::flash('success', 'Rezerwacja anulowana.');
        $this->redirect('bookings');
    }
}
