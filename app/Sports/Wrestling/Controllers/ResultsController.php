<?php

namespace App\Sports\Wrestling\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Wrestling\Models\WrestlingResultModel;

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
        $results = (new WrestlingResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('wrestling/results/index', [
            'title'              => 'Wyniki zawodów — Zapasy',
            'results'            => $results,
            'members'            => $members,
            'styles'             => WrestlingResultModel::$STYLES,
            'weightClassesMen'   => WrestlingResultModel::$WEIGHT_CLASSES_MEN,
            'weightClassesWomen' => WrestlingResultModel::$WEIGHT_CLASSES_WOMEN,
            'weightClassesGreco' => WrestlingResultModel::$WEIGHT_CLASSES_GRECO,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('wrestling/results');
        }

        $competitionName = trim($_POST['competition_name'] ?? '');
        if ($competitionName === '') {
            Session::flash('error', 'Podaj nazwę zawodów.');
            $this->redirect('wrestling/results');
        }

        $style = $_POST['style'] ?? 'freestyle';
        if (!array_key_exists($style, WrestlingResultModel::$STYLES)) {
            $style = 'freestyle';
        }

        $placement = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;

        (new WrestlingResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => $competitionName,
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'style'            => $style,
            'weight_class'     => trim($_POST['weight_class'] ?? '') ?: null,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);

        Session::flash('success', 'Wynik dodany.');
        $this->redirect('wrestling/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new WrestlingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('wrestling/results');
    }
}
