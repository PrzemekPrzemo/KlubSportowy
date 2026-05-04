<?php

namespace App\Sports\Powerlifting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Powerlifting\Models\PowerliftingResultModel;

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
        $results = (new PowerliftingResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('powerlifting/results/index', [
            'title'           => 'Wyniki zawodów — Trójbój siłowy',
            'results'         => $results,
            'members'         => $members,
            'weightClassesMen'   => PowerliftingResultModel::$WEIGHT_CLASSES_MEN,
            'weightClassesWomen' => PowerliftingResultModel::$WEIGHT_CLASSES_WOMEN,
            'federations'     => PowerliftingResultModel::$FEDERATIONS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('powerlifting/results');
        }

        $competitionName = trim($_POST['competition_name'] ?? '');
        if ($competitionName === '') {
            Session::flash('error', 'Podaj nazwę zawodów.');
            $this->redirect('powerlifting/results');
        }

        $weightClass = trim($_POST['weight_class'] ?? '');
        if ($weightClass === '') {
            Session::flash('error', 'Podaj kategorię wagową.');
            $this->redirect('powerlifting/results');
        }

        $federationType = $_POST['federation_type'] ?? 'PZTSS';
        if (!array_key_exists($federationType, PowerliftingResultModel::$FEDERATIONS)) {
            $federationType = 'PZTSS';
        }

        $bodyWeight  = !empty($_POST['body_weight'])  ? (float)$_POST['body_weight']  : null;
        $squatBest   = !empty($_POST['squat_best'])   ? (float)$_POST['squat_best']   : null;
        $benchBest   = !empty($_POST['bench_best'])   ? (float)$_POST['bench_best']   : null;
        $deadliftBest = !empty($_POST['deadlift_best']) ? (float)$_POST['deadlift_best'] : null;

        // Auto-compute total
        $total = PowerliftingResultModel::calcTotal($squatBest, $benchBest, $deadliftBest);

        // Compute Wilks if body_weight and total are available
        $wilks = null;
        if ($total !== null && $bodyWeight !== null && $bodyWeight > 0) {
            $sex   = trim($_POST['sex'] ?? 'M');
            $sex   = in_array($sex, ['M', 'F'], true) ? $sex : 'M';
            $wilks = PowerliftingResultModel::wilks($total, $bodyWeight, $sex);
        }

        $placement = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;

        (new PowerliftingResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => $competitionName,
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'weight_class'     => $weightClass,
            'body_weight'      => $bodyWeight,
            'squat_best'       => $squatBest,
            'bench_best'       => $benchBest,
            'deadlift_best'    => $deadliftBest,
            'total'            => $total,
            'wilks_coeff'      => $wilks,
            'ipf_gl_points'    => !empty($_POST['ipf_gl_points']) ? (float)$_POST['ipf_gl_points'] : null,
            'federation_type'  => $federationType,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);

        Session::flash('success', 'Wynik dodany.');
        $this->redirect('powerlifting/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new PowerliftingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('powerlifting/results');
    }
}
