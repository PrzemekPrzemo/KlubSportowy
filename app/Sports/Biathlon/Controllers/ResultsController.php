<?php

namespace App\Sports\Biathlon\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportResultsCrudTrait;
use App\Models\MemberModel;
use App\Sports\Biathlon\Models\BiathlonResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;
    use SportResultsCrudTrait;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('biathlon');
    }

    protected function crudConfig(): array
    {
        return [
            'model'         => new BiathlonResultModel(),
            'table'         => 'biathlon_results',
            'index_route'   => 'biathlon/results',
            'view_prefix'   => 'biathlon/results',
            'title_show'    => 'Szczegóły wyniku — Biathlon',
            'title_edit'    => 'Edytuj wynik — Biathlon',
            'extra_selects' => [
                'format' => ['label' => 'Format', 'options' => BiathlonResultModel::$FORMATS],
            ],
        ];
    }

    public function index(): void
    {
        $model = new BiathlonResultModel();
        $this->render('biathlon/results/index', [
            'title'   => 'Wyniki — Biathlon',
            'results' => $model->listForClub(),
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'formats' => BiathlonResultModel::$FORMATS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('biathlon/results'); }
        $format = array_key_exists($_POST['format'] ?? '', BiathlonResultModel::$FORMATS) ? $_POST['format'] : 'sprint';

        $runTime = !empty($_POST['run_time_s']) ? (int)$_POST['run_time_s'] : null;
        $penaltyTime = !empty($_POST['penalty_time_s']) ? (int)$_POST['penalty_time_s'] : 0;
        $totalTime = $runTime !== null ? $runTime + $penaltyTime : null;

        (new BiathlonResultModel())->insert([
            'member_id'       => $memberId,
            'format'          => $format,
            'event_name'      => trim($_POST['event_name'] ?? '') ?: 'Zawody',
            'event_date'      => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'           => trim($_POST['venue'] ?? '') ?: null,
            'category'        => trim($_POST['category'] ?? '') ?: null,
            'distance_km'     => (float)($_POST['distance_km'] ?? 0),
            'run_time_s'      => $runTime,
            'shootings_total' => !empty($_POST['shootings_total']) ? (int)$_POST['shootings_total'] : null,
            'misses_total'    => !empty($_POST['misses_total']) ? (int)$_POST['misses_total'] : null,
            'penalty_laps'    => (int)($_POST['penalty_laps'] ?? 0),
            'penalty_time_s'  => $penaltyTime,
            'total_time_s'    => $totalTime,
            'place'           => !empty($_POST['place']) ? (int)$_POST['place'] : null,
            'fis_points'      => !empty($_POST['fis_points']) ? (float)$_POST['fis_points'] : null,
            'dnf'             => isset($_POST['dnf']) ? 1 : 0,
            'dns'             => isset($_POST['dns']) ? 1 : 0,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('biathlon/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BiathlonResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('biathlon/results');
    }
}
