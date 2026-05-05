<?php

namespace App\Sports\XcSki\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportResultsCrudTrait;
use App\Models\MemberModel;
use App\Sports\XcSki\Models\XcSkiResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;
    use SportResultsCrudTrait;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('xcski');
    }

    protected function crudConfig(): array
    {
        return [
            'model'         => new XcSkiResultModel(),
            'table'         => 'xc_ski_results',
            'index_route'   => 'xcski/results',
            'view_prefix'   => 'xcski/results',
            'title_show'    => 'Szczegóły wyniku — Narciarstwo biegowe',
            'title_edit'    => 'Edytuj wynik — Narciarstwo biegowe',
            'extra_selects' => [
                'technique' => ['label' => 'Technika', 'options' => XcSkiResultModel::$TECHNIQUES],
            ],
        ];
    }

    public function index(): void
    {
        $tech = $_GET['technique'] ?? null;
        $model = new XcSkiResultModel();
        $this->render('xcski/results/index', [
            'title'      => 'Wyniki — Narciarstwo biegowe',
            'results'    => $model->listForClub(null, $tech),
            'members'    => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'techniques' => XcSkiResultModel::$TECHNIQUES,
            'techFilter' => $tech,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('xcski/results'); }
        $tech = array_key_exists($_POST['technique'] ?? '', XcSkiResultModel::$TECHNIQUES) ? $_POST['technique'] : 'classic';

        (new XcSkiResultModel())->insert([
            'member_id'   => $memberId,
            'technique'   => $tech,
            'distance_km' => (float)($_POST['distance_km'] ?? 0),
            'event_name'  => trim($_POST['event_name'] ?? '') ?: 'Zawody',
            'event_date'  => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'       => trim($_POST['venue'] ?? '') ?: null,
            'category'    => trim($_POST['category'] ?? '') ?: null,
            'time_s'      => !empty($_POST['time_s']) ? (int)$_POST['time_s'] : null,
            'place'       => !empty($_POST['place']) ? (int)$_POST['place'] : null,
            'fis_points'  => !empty($_POST['fis_points']) ? (float)$_POST['fis_points'] : null,
            'dnf'         => isset($_POST['dnf']) ? 1 : 0,
            'dns'         => isset($_POST['dns']) ? 1 : 0,
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('xcski/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new XcSkiResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('xcski/results');
    }
}
