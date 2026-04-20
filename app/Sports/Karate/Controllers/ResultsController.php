<?php

namespace App\Sports\Karate\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Karate\Models\KarateResultModel;

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
        $results = (new KarateResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('karate/results/index', [
            'title'        => 'Wyniki zawodów — Karate',
            'results'      => $results,
            'members'      => $members,
            'weightClasses' => KarateResultModel::$WEIGHT_CLASSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('karate/results');
        }

        $placement = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;
        $category  = in_array($_POST['category'] ?? '', ['kumite','kata','team_kumite','team_kata'], true)
                        ? $_POST['category'] : 'kumite';
        $wc        = in_array($_POST['weight_class'] ?? '', KarateResultModel::$WEIGHT_CLASSES, true)
                        ? $_POST['weight_class'] : null;

        (new KarateResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'weight_class'     => $wc,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'category'         => $category,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('karate/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new KarateResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('karate/results');
    }
}
