<?php

namespace App\Sports\Padel\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Sports\Padel\Models\PadelReservationModel;

class ReservationsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model  = new PadelReservationModel();
        $clubId = $model->clubId();

        $courts = Database::pdo()->prepare(
            "SELECT * FROM padel_courts WHERE club_id = ? AND is_active = 1 ORDER BY name"
        );
        $courts->execute([$clubId]);
        $courts = $courts->fetchAll();

        $week      = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
        $courtId   = !empty($_GET['court']) ? (int)$_GET['court'] : (int)(($courts[0]['id'] ?? 0));
        $calendar  = $courtId > 0 ? $model->weeklyCalendar($courtId, $week) : [];

        $this->render('padel/reservations/index', [
            'title'       => 'Rezerwacje kortów — Padel',
            'courts'      => $courts,
            'reservations'=> $model->listForClub(),
            'calendar'    => $calendar,
            'selectedCourt' => $courtId,
            'week'        => $week,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $model    = new PadelReservationModel();
        $courtId  = (int)($_POST['court_id'] ?? 0);
        $memberId = (int)($_POST['member_id'] ?? 0);
        $start    = trim($_POST['start_datetime'] ?? '');
        $end      = trim($_POST['end_datetime'] ?? '');

        if (!$courtId || !$memberId || !$start || !$end) {
            Session::flash('error', 'Uzupełnij wszystkie pola.');
            $this->redirect('padel/reservations');
        }

        if (!$model->isAvailable($courtId, $start, $end)) {
            Session::flash('error', 'Kort zajęty w wybranym terminie.');
            $this->redirect('padel/reservations');
        }

        $model->insert([
            'court_id'       => $courtId,
            'member_id'      => $memberId,
            'start_datetime' => $start,
            'end_datetime'   => $end,
            'status'         => 'pending',
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Rezerwacja złożona — oczekuje na potwierdzenie.');
        $this->redirect('padel/reservations');
    }

    public function confirm(string $id): void
    {
        Csrf::verify();
        $model = new PadelReservationModel();
        Database::pdo()->prepare(
            "UPDATE padel_reservations SET status='confirmed' WHERE id=? AND club_id=?"
        )->execute([(int)$id, $model->clubId()]);
        Session::flash('success', 'Rezerwacja potwierdzona.');
        $this->redirect('padel/reservations');
    }

    public function cancel(string $id): void
    {
        Csrf::verify();
        $model = new PadelReservationModel();
        Database::pdo()->prepare(
            "UPDATE padel_reservations SET status='cancelled' WHERE id=? AND club_id=?"
        )->execute([(int)$id, $model->clubId()]);
        Session::flash('success', 'Rezerwacja anulowana.');
        $this->redirect('padel/reservations');
    }
}
