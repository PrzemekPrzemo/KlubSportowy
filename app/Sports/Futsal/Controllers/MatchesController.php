<?php

namespace App\Sports\Futsal\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Futsal\Models\FutsalMatchModel;
use App\Sports\Futsal\Models\FutsalMatchStatsModel;
use App\Sports\Futsal\Models\FutsalTeamModel;

class MatchesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $mModel = new FutsalMatchModel();
        $tModel = new FutsalTeamModel();
        $this->render('futsal/matches/index', [
            'title'    => 'Mecze — Futsal',
            'matches'  => $mModel->listForClub(),
            'teams'    => $tModel->listForClub(),
            'statuses' => FutsalMatchModel::$STATUSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $home = (int)($_POST['home_team_id'] ?? 0);
        if ($home <= 0) {
            Session::flash('error', 'Wybierz drużynę gospodarzy.');
            $this->redirect('futsal/matches');
        }
        $status = array_key_exists($_POST['status'] ?? '', FutsalMatchModel::$STATUSES)
            ? $_POST['status'] : 'zaplanowany';
        (new FutsalMatchModel())->insert([
            'home_team_id'   => $home,
            'away_team_name' => trim($_POST['away_team_name'] ?? '') ?: null,
            'match_date'     => trim($_POST['match_date'] ?? '') ?: date('Y-m-d H:i:s'),
            'location'       => trim($_POST['location'] ?? '') ?: null,
            'home_score'     => max(0, (int)($_POST['home_score'] ?? 0)),
            'away_score'     => max(0, (int)($_POST['away_score'] ?? 0)),
            'status'         => $status,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Mecz zapisany.');
        $this->redirect('futsal/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FutsalMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('futsal/matches');
    }

    public function statsForm(string $matchId): void
    {
        $sModel = new FutsalMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('futsal/matches');
        }
        $this->render('futsal/matches/stats_form', [
            'title'         => 'Statystyki meczu — Futsal',
            'match'         => $match,
            'stats'         => $sModel->forMatch((int)$matchId),
            'statsColumns'  => $sModel->statsColumns(),
            'sportKey'      => 'futsal',
            'submitUrl'     => 'futsal/matches/' . (int)$matchId . '/stats',
            'backUrl'       => 'futsal/matches',
        ]);
    }

    public function statsSave(string $matchId): void
    {
        Csrf::verify();
        $sModel = new FutsalMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('futsal/matches');
        }
        foreach (['home', 'away'] as $side) {
            $payload = $_POST[$side] ?? [];
            if (!is_array($payload)) continue;
            $sModel->upsert((int)$matchId, $side, $payload);
        }
        Session::flash('success', 'Statystyki zapisane.');
        $this->redirect('futsal/matches');
    }
}
