<?php

namespace App\Sports\WaterPolo\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\WaterPolo\Models\WaterPoloMatchModel;
use App\Sports\WaterPolo\Models\WaterPoloMatchStatsModel;
use App\Sports\WaterPolo\Models\WaterPoloTeamModel;

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
        $mModel = new WaterPoloMatchModel();
        $tModel = new WaterPoloTeamModel();
        $this->render('water_polo/matches/index', [
            'title'    => 'Mecze — Piłka wodna',
            'matches'  => $mModel->listForClub(),
            'teams'    => $tModel->listForClub(),
            'statuses' => WaterPoloMatchModel::$STATUSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $home = (int)($_POST['home_team_id'] ?? 0);
        if ($home <= 0) {
            Session::flash('error', 'Wybierz drużynę gospodarzy.');
            $this->redirect('water_polo/matches');
        }
        $status = array_key_exists($_POST['status'] ?? '', WaterPoloMatchModel::$STATUSES)
            ? $_POST['status'] : 'zaplanowany';
        (new WaterPoloMatchModel())->insert([
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
        $this->redirect('water_polo/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new WaterPoloMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('water_polo/matches');
    }

    public function statsForm(string $matchId): void
    {
        $sModel = new WaterPoloMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('water_polo/matches');
        }
        $this->render('water_polo/matches/stats_form', [
            'title'        => 'Statystyki meczu — Piłka wodna',
            'match'        => $match,
            'stats'        => $sModel->forMatch((int)$matchId),
            'statsColumns' => $sModel->statsColumns(),
            'sportKey'     => 'water_polo',
            'submitUrl'    => 'water_polo/matches/' . (int)$matchId . '/stats',
            'backUrl'      => 'water_polo/matches',
        ]);
    }

    public function statsSave(string $matchId): void
    {
        Csrf::verify();
        $sModel = new WaterPoloMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('water_polo/matches');
        }
        foreach (['home', 'away'] as $side) {
            $payload = $_POST[$side] ?? [];
            if (!is_array($payload)) continue;
            $sModel->upsert((int)$matchId, $side, $payload);
        }
        Session::flash('success', 'Statystyki zapisane.');
        $this->redirect('water_polo/matches');
    }
}
