<?php

namespace App\Sports\CrossFit\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\CrossFitResultModel;
use App\Sports\Support\Models\CrossFitWodLibraryModel;

/**
 * Leaderboard per WOD + zapis wynikow z flaga RX/scaled/foundations.
 * Tabela: sport_crossfit_results (z 106_scoring_niche_full.sql).
 */
class LeaderboardController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('crossfit');
    }

    public function index(): void
    {
        $wodId = !empty($_GET['wod_id']) ? (int)$_GET['wod_id'] : null;
        $wodLib = new CrossFitWodLibraryModel();
        $resultsModel = new CrossFitResultModel();

        $leaderboard = [];
        $selectedWod = null;
        if ($wodId !== null) {
            $selectedWod = $wodLib->findById($wodId);
            if ($selectedWod) {
                $leaderboard = $resultsModel->leaderboard((int)$selectedWod['id'], (string)$selectedWod['type']);
            }
        }

        $this->render('crossfit/leaderboard/index', [
            'title'       => 'Leaderboard — CrossFit',
            'wods'        => $wodLib->listAvailable(),
            'selectedWod' => $selectedWod,
            'leaderboard' => $leaderboard,
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'levels'      => CrossFitResultModel::$LEVELS,
            'wodTypes'    => CrossFitWodLibraryModel::$TYPES,
        ]);
    }

    public function storeResult(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $wodId    = (int)($_POST['wod_id']    ?? 0);
        if ($memberId <= 0 || $wodId <= 0) {
            Session::flash('error', 'Wybierz zawodnika i WOD.');
            $this->redirect('crossfit/leaderboard');
        }
        $level = array_key_exists($_POST['scaled_or_rx'] ?? '', CrossFitResultModel::$LEVELS)
            ? $_POST['scaled_or_rx'] : 'RX';

        (new CrossFitResultModel())->insert([
            'member_id'           => $memberId,
            'wod_id'              => $wodId,
            'scaled_or_rx'        => $level,
            'result_time_seconds' => isset($_POST['result_time_seconds']) && $_POST['result_time_seconds'] !== '' ? (int)$_POST['result_time_seconds'] : null,
            'result_reps'         => isset($_POST['result_reps']) && $_POST['result_reps'] !== '' ? (int)$_POST['result_reps'] : null,
            'result_load_kg'      => isset($_POST['result_load_kg']) && $_POST['result_load_kg'] !== '' ? (float)$_POST['result_load_kg'] : null,
            'recorded_at'         => trim((string)($_POST['recorded_at'] ?? '')) ?: date('Y-m-d'),
            'verified'            => !empty($_POST['verified']) ? 1 : 0,
            'notes'               => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Wynik zapisany.');
        $this->redirect('crossfit/leaderboard?wod_id=' . $wodId);
    }

    public function deleteResult(string $id): void
    {
        Csrf::verify();
        (new CrossFitResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('crossfit/leaderboard');
    }
}
