<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Equestrian\Models\EquestrianHorseModel;
use App\Sports\Equestrian\Models\EquestrianResultModel;

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
        $results = (new EquestrianResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $horses  = (new EquestrianHorseModel())->listForClub();
        $this->render('equestrian/results/index', [
            'title'       => 'Wyniki zawodów — Jeździectwo',
            'results'     => $results,
            'members'     => $members,
            'horses'      => $horses,
            'disciplines' => EquestrianHorseModel::$DISCIPLINES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('equestrian/results');
        }

        $discipline = array_key_exists($_POST['discipline'] ?? '', EquestrianHorseModel::$DISCIPLINES)
                        ? $_POST['discipline'] : 'jumping';
        $horseId    = (int)($_POST['horse_id'] ?? 0) ?: null;

        (new EquestrianResultModel())->insert([
            'member_id'        => $memberId,
            'horse_id'         => $horseId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'discipline'       => $discipline,
            'class_level'      => trim($_POST['class_level'] ?? '') ?: null,
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'score'            => !empty($_POST['score']) ? (float)$_POST['score'] : null,
            'faults'           => !empty($_POST['faults']) ? (float)$_POST['faults'] : null,
            'time_seconds'     => !empty($_POST['time_seconds']) ? (float)$_POST['time_seconds'] : null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('equestrian/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new EquestrianResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('equestrian/results');
    }
}
