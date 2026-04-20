<?php

namespace App\Sports\Weightlifting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Weightlifting\Models\WeightliftingResultModel;

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
        $results = (new WeightliftingResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('weightlifting/results/index', [
            'title'              => 'Wyniki zawodów — Podnoszenie ciężarów',
            'results'            => $results,
            'members'            => $members,
            'weightClassesMen'   => WeightliftingResultModel::$WEIGHT_CLASSES_MEN,
            'weightClassesWomen' => WeightliftingResultModel::$WEIGHT_CLASSES_WOMEN,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('weightlifting/results');
        }

        $competitionName = trim($_POST['competition_name'] ?? '');
        if ($competitionName === '') {
            Session::flash('error', 'Podaj nazwę zawodów.');
            $this->redirect('weightlifting/results');
        }

        $weightClass = trim($_POST['weight_class'] ?? '');
        if ($weightClass === '') {
            Session::flash('error', 'Podaj kategorię wagową.');
            $this->redirect('weightlifting/results');
        }

        $bodyWeight  = !empty($_POST['body_weight'])   ? (float)$_POST['body_weight']   : null;
        $snatchBest  = !empty($_POST['snatch_best'])   ? (float)$_POST['snatch_best']   : null;
        $cjBest      = !empty($_POST['cleanjerk_best']) ? (float)$_POST['cleanjerk_best'] : null;

        // Auto-compute total
        $total = WeightliftingResultModel::calcTotal($snatchBest, $cjBest);

        // Compute Sinclair if body_weight and total available
        $sinclair = null;
        if ($total !== null && $bodyWeight !== null && $bodyWeight > 0) {
            $sex      = in_array($_POST['sex'] ?? 'M', ['M', 'F'], true) ? $_POST['sex'] : 'M';
            $sinclair = WeightliftingResultModel::sinclair($total, $bodyWeight, $sex);
        }

        $placement = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;

        (new WeightliftingResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => $competitionName,
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'weight_class'     => $weightClass,
            'body_weight'      => $bodyWeight,
            'snatch_best'      => $snatchBest,
            'cleanjerk_best'   => $cjBest,
            'total'            => $total,
            'sinclair_coeff'   => $sinclair,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);

        Session::flash('success', 'Wynik dodany.');
        $this->redirect('weightlifting/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new WeightliftingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('weightlifting/results');
    }
}
