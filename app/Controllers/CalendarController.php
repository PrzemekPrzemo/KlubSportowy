<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\CalendarEventCategoryModel;
use App\Models\CalendarEventModel;
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
