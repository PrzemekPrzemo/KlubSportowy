<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\EventModel;
use App\Models\SportModel;

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
}
