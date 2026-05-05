<?php

namespace App\Sports\Wrestling\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\MemberModel;
use App\Sports\Wrestling\Models\WrestlingResultModel;

class ResultsController extends BaseController
{
    use ValidatesRequest;

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

    public function show(string $id): void
    {
        $row = (new WrestlingResultModel())->findById((int)$id);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect('wrestling/results');
        }
        $member = (new MemberModel())->findById((int)$row['member_id']);
        $this->render('wrestling/results/show', [
            'title'  => 'Szczegóły wyniku',
            'result' => $row,
            'member' => $member,
            'styles' => WrestlingResultModel::$STYLES,
        ]);
    }

    public function edit(string $id): void
    {
        $row = (new WrestlingResultModel())->findById((int)$id);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect('wrestling/results');
        }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('wrestling/results/edit', [
            'title'              => 'Edytuj wynik — Zapasy',
            'result'             => $row,
            'members'            => $members,
            'styles'             => WrestlingResultModel::$STYLES,
            'weightClassesMen'   => WrestlingResultModel::$WEIGHT_CLASSES_MEN,
            'weightClassesWomen' => WrestlingResultModel::$WEIGHT_CLASSES_WOMEN,
            'weightClassesGreco' => WrestlingResultModel::$WEIGHT_CLASSES_GRECO,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        $back  = 'wrestling/results';

        $row = (new WrestlingResultModel())->findById($idInt);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono wpisu.');
            $this->redirect($back);
        }

        $memberId        = $this->validateInt($_POST['member_id'] ?? '', 'member_id', 1, null, $back);
        $competitionName = $this->validateString($_POST['competition_name'] ?? '', 'competition_name', 1, 200, $back);
        $competitionDate = $this->validateDate($_POST['competition_date'] ?? '', 'competition_date', $back);
        $style           = $this->validateInList($_POST['style'] ?? '', WrestlingResultModel::$STYLES, 'style', $back);

        (new WrestlingResultModel())->update($idInt, [
            'member_id'        => $memberId,
            'competition_name' => $competitionName,
            'competition_date' => $competitionDate,
            'style'            => $style,
            'weight_class'     => $this->validateOptionalString($_POST['weight_class'] ?? null, 15, $back),
            'age_category'     => $this->validateOptionalString($_POST['age_category'] ?? null, 50, $back),
            'placement'        => $this->validateOptionalInt($_POST['placement'] ?? null, 1, 99, $back),
            'notes'            => $this->validateOptionalString($_POST['notes'] ?? null, 5000, $back),
        ]);

        Session::flash('success', 'Zaktualizowano wynik.');
        $this->redirect($back);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new WrestlingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('wrestling/results');
    }
}
