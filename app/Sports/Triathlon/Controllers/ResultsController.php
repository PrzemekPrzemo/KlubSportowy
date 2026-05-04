<?php

namespace App\Sports\Triathlon\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Triathlon\Models\TriathlonResultModel;

class ResultsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $distance = $_GET['distance'] ?? null;
        if ($distance !== null && !in_array($distance, TriathlonResultModel::$DISTANCES, true)) {
            $distance = null;
        }
        $year = !empty($_GET['year']) ? (int)$_GET['year'] : null;

        $this->render('triathlon/results/index', [
            'title'      => 'Wyniki — Triathlon',
            'results'    => (new TriathlonResultModel())->listForClub(null, $distance, $year),
            'members'    => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'distances'  => TriathlonResultModel::$DISTANCES,
            'filterDist' => $distance,
            'filterYear' => $year,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if (!$memberId) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('triathlon/results'); }

        $dist = in_array($_POST['distance_type'] ?? '', TriathlonResultModel::$DISTANCES, true)
            ? $_POST['distance_type'] : 'olympic';

        $swim = !empty($_POST['swim_time']) ? (int)$_POST['swim_time'] : null;
        $t1   = !empty($_POST['t1_time'])   ? (int)$_POST['t1_time']   : null;
        $bike = !empty($_POST['bike_time']) ? (int)$_POST['bike_time'] : null;
        $t2   = !empty($_POST['t2_time'])   ? (int)$_POST['t2_time']   : null;
        $run  = !empty($_POST['run_time'])  ? (int)$_POST['run_time']  : null;
        $total = ($swim !== null && $t1 !== null && $bike !== null && $t2 !== null && $run !== null)
            ? $swim + $t1 + $bike + $t2 + $run
            : (!empty($_POST['total_time']) ? (int)$_POST['total_time'] : null);

        (new TriathlonResultModel())->insert([
            'member_id'         => $memberId,
            'event_name'        => trim($_POST['event_name'] ?? ''),
            'event_date'        => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'distance_type'     => $dist,
            'swim_time'         => $swim,
            't1_time'           => $t1,
            'bike_time'         => $bike,
            't2_time'           => $t2,
            'run_time'          => $run,
            'total_time'        => $total,
            'age_group'         => trim($_POST['age_group'] ?? '') ?: null,
            'ag_placement'      => !empty($_POST['ag_placement']) ? (int)$_POST['ag_placement'] : null,
            'overall_placement' => !empty($_POST['overall_placement']) ? (int)$_POST['overall_placement'] : null,
            'dnf'               => !empty($_POST['dnf']) ? 1 : 0,
            'dns'               => !empty($_POST['dns']) ? 1 : 0,
            'notes'             => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('triathlon/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new TriathlonResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('triathlon/results');
    }
}
