<?php

namespace App\Sports\Swimming\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Swimming\Models\SwimmingResultModel;

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
        $model   = new SwimmingResultModel();
        $results = $model->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('swimming/results/index', [
            'title'     => 'Wyniki pływania',
            'results'   => $results,
            'members'   => $members,
            'strokes'   => SwimmingResultModel::$STROKES,
            'distances' => SwimmingResultModel::$DISTANCES,
            'poolTypes' => SwimmingResultModel::$POOL_TYPES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('swimming/results');
        }

        $stroke = $_POST['stroke'] ?? '';
        if (!array_key_exists($stroke, SwimmingResultModel::$STROKES)) {
            Session::flash('error', 'Nieprawidłowy styl pływania.');
            $this->redirect('swimming/results');
        }

        $distanceM = (int)($_POST['distance_m'] ?? 0);
        if (!in_array($distanceM, SwimmingResultModel::$DISTANCES, true)) {
            Session::flash('error', 'Nieprawidłowy dystans.');
            $this->redirect('swimming/results');
        }

        $poolType = $_POST['pool_type'] ?? '25m';
        if (!array_key_exists($poolType, SwimmingResultModel::$POOL_TYPES)) {
            $poolType = '25m';
        }

        // Compute time_ms from individual inputs
        $timeMin = max(0, (int)($_POST['time_min'] ?? 0));
        $timeSec = max(0, min(59, (int)($_POST['time_sec'] ?? 0)));
        $timeCs  = max(0, min(99, (int)($_POST['time_cs'] ?? 0)));
        $timeMs  = ($timeMin * 60 + $timeSec) * 1000 + $timeCs * 10;

        if ($timeMs <= 0) {
            Session::flash('error', 'Wprowadź prawidłowy czas.');
            $this->redirect('swimming/results');
        }

        $placement   = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;
        $personalBest = isset($_POST['personal_best']) ? 1 : 0;

        $model = new SwimmingResultModel();

        // Auto-check personal best: is this the best time for this member+stroke+distance?
        if (!$personalBest) {
            $currentBest = $model->bestTime($memberId, $stroke, $distanceM);
            if ($currentBest === null || $timeMs < $currentBest) {
                $personalBest = 1;
            }
        }

        $model->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? '') ?: null,
            'score_date'       => trim($_POST['score_date'] ?? '') ?: date('Y-m-d'),
            'stroke'           => $stroke,
            'distance_m'       => $distanceM,
            'pool_type'        => $poolType,
            'time_ms'          => $timeMs,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'personal_best'    => $personalBest,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);

        Session::flash('success', 'Wynik dodany.');
        $this->redirect('swimming/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SwimmingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('swimming/results');
    }
}
