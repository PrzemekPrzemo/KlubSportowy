<?php

namespace App\Sports\CrossFit\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\CrossFit\Models\CrossFitWodModel;

class WodsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $type   = $_GET['type'] ?? null;
        $model  = new CrossFitWodModel();
        $wods   = $model->listForClub($type);

        $boards = [];
        foreach ($wods as $w) {
            $boards[$w['id']] = $model->leaderboard((int)$w['id'], 5);
        }

        $this->render('crossfit/wods/index', [
            'title'    => 'WOD Library — CrossFit',
            'wods'     => $wods,
            'boards'   => $boards,
            'members'  => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'wodTypes' => CrossFitWodModel::$WOD_TYPES,
            'filterType' => $type,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if (!$name) { Session::flash('error', 'Podaj nazwę WOD.'); $this->redirect('crossfit/wods'); }

        $type = array_key_exists($_POST['wod_type'] ?? '', CrossFitWodModel::$WOD_TYPES)
            ? $_POST['wod_type'] : 'for_time';

        (new CrossFitWodModel())->insert([
            'name'           => $name,
            'wod_type'       => $type,
            'description'    => trim($_POST['description'] ?? '') ?: null,
            'time_cap'       => !empty($_POST['time_cap']) ? (int)$_POST['time_cap'] : null,
            'benchmark_name' => trim($_POST['benchmark_name'] ?? '') ?: null,
        ]);
        Session::flash('success', 'WOD dodany.');
        $this->redirect('crossfit/wods');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new CrossFitWodModel())->delete((int)$id);
        Session::flash('success', 'Usunięto WOD.');
        $this->redirect('crossfit/wods');
    }

    public function addScore(string $id): void
    {
        Csrf::verify();
        $wodId    = (int)$id;
        $memberId = (int)($_POST['member_id'] ?? 0);
        $score    = trim($_POST['score'] ?? '');

        if (!$memberId || !$score) {
            Session::flash('error', 'Wybierz zawodnika i podaj wynik.');
            $this->redirect('crossfit/wods');
        }

        (new CrossFitWodModel())->addScore($wodId, $memberId, [
            'score'      => $score,
            'rx'         => !empty($_POST['rx']),
            'scaled'     => !empty($_POST['scaled']),
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
            'score_date' => trim($_POST['score_date'] ?? '') ?: date('Y-m-d'),
        ]);
        Session::flash('success', 'Wynik WOD dodany.');
        $this->redirect('crossfit/wods');
    }
}
