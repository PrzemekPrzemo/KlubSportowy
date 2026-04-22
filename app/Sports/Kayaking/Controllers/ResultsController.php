<?php

namespace App\Sports\Kayaking\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Kayaking\Models\KayakBoatModel;
use App\Sports\Kayaking\Models\KayakResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('kayaking');
    }

    public function index(): void
    {
        $this->render('kayaking/results/index', [
            'title'       => 'Wyniki — Kajakarstwo',
            'results'     => (new KayakResultModel())->listForClub(),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'boats'       => (new KayakBoatModel())->listForClub(),
            'disciplines' => KayakResultModel::$DISCIPLINES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('kayaking/results'); }
        $disc = array_key_exists($_POST['discipline'] ?? '', KayakResultModel::$DISCIPLINES) ? $_POST['discipline'] : 'sprint';

        (new KayakResultModel())->insert([
            'member_id'  => $memberId,
            'boat_id'    => !empty($_POST['boat_id']) ? (int)$_POST['boat_id'] : null,
            'discipline' => $disc,
            'event_name' => trim($_POST['event_name'] ?? '') ?: 'Zawody',
            'event_date' => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'      => trim($_POST['venue'] ?? '') ?: null,
            'category'   => trim($_POST['category'] ?? '') ?: null,
            'distance_m' => (int)($_POST['distance_m'] ?? 0),
            'time_ms'    => !empty($_POST['time_ms']) ? (int)$_POST['time_ms'] : null,
            'place'      => !empty($_POST['place'])   ? (int)$_POST['place'] : null,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('kayaking/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new KayakResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('kayaking/results');
    }
}
