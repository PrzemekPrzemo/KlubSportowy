<?php

namespace App\Sports\Gymnastics\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Gymnastics\Models\GymnasticsResultModel;

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
        $discipline = $_GET['discipline'] ?? null;
        if ($discipline !== null && !in_array($discipline, GymnasticsResultModel::$DISCIPLINES, true)) {
            $discipline = null;
        }

        $this->render('gymnastics/results/index', [
            'title'       => 'Wyniki — Gimnastyka',
            'results'     => (new GymnasticsResultModel())->listForClub($discipline),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'disciplines' => GymnasticsResultModel::$DISCIPLINES,
            'filterDisc'  => $discipline,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('gymnastics/results');
        }

        $discipline = in_array($_POST['discipline'] ?? '', GymnasticsResultModel::$DISCIPLINES, true)
            ? $_POST['discipline'] : 'artystyczna';

        $dScore = max(0.0, (float)($_POST['difficulty_score'] ?? 0));
        $eScore = max(0.0, (float)($_POST['execution_score'] ?? 0));
        $pScore = max(0.0, (float)($_POST['penalty_score'] ?? 0));

        (new GymnasticsResultModel())->insert([
            'member_id'        => $memberId,
            'discipline'       => $discipline,
            'apparatus'        => trim($_POST['apparatus'] ?? '') ?: null,
            'event_name'       => trim($_POST['event_name'] ?? ''),
            'event_date'       => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'difficulty_score' => round($dScore, 3),
            'execution_score'  => round($eScore, 3),
            'penalty_score'    => round($pScore, 3),
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('gymnastics/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new GymnasticsResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('gymnastics/results');
    }
}
