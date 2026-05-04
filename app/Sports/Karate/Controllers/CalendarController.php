<?php

namespace App\Sports\Karate\Controllers;

use App\Controllers\BaseController;
use App\Models\EventModel;
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
        $sport   = (new SportModel())->findByKey('karate');
        $sportId = $sport ? (int)$sport['id'] : null;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $from    = $_GET['from'] ?? null;
        $pagination = (new EventModel())->listForClub($sportId, null, $from, $page, 20);

        $this->render('sport_calendar/index', [
            'title'       => 'Kalendarz zawodów — Karate',
            'sportName'   => 'Karate',
            'sportKey'    => 'karate',
            'pagination'  => $pagination,
            'from'        => $from,
        ]);
    }
}
