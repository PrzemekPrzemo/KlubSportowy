<?php

namespace App\Sports\Floorball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Floorball\Models\FloorballMatchModel;
use App\Sports\Floorball\Models\FloorballMatchStatsModel;
use App\Sports\Floorball\Models\FloorballTeamModel;

class StatsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function statsForm(string $matchId): void
    {
        $sModel = new FloorballMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('floorball/matches');
        }
        $this->render('floorball/matches/stats_form', [
            'title'        => 'Statystyki meczu — Floorball',
            'match'        => $match,
            'stats'        => $sModel->forMatch((int)$matchId),
            'statsColumns' => $sModel->statsColumns(),
            'sportKey'     => 'floorball',
            'submitUrl'    => 'floorball/matches/' . (int)$matchId . '/stats',
            'backUrl'      => 'floorball/matches',
        ]);
    }

    public function statsSave(string $matchId): void
    {
        Csrf::verify();
        $sModel = new FloorballMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('floorball/matches');
        }
        foreach (['home', 'away'] as $side) {
            $payload = $_POST[$side] ?? [];
            if (!is_array($payload)) continue;
            $sModel->upsert((int)$matchId, $side, $payload);
        }
        Session::flash('success', 'Statystyki zapisane.');
        $this->redirect('floorball/matches');
    }

    public function dashboard(): void
    {
        $mModel = new FloorballMatchModel();
        $tModel = new FloorballTeamModel();

        $teams      = $tModel->listForClub();
        $allScorers = [];
        foreach ($teams as $t) {
            foreach ($mModel->topScorers((int)$t['id'], 5) as $row) {
                $row['team_name'] = $t['name'];
                $allScorers[] = $row;
            }
        }

        $this->render('floorball/stats/dashboard', [
            'title'      => 'Dashboard — Floorball',
            'teams'      => $teams,
            'topScorers' => $allScorers,
            'recent'     => array_slice($mModel->schedule(), 0, 10),
            'sportKey'   => 'floorball',
            'sportLabel' => 'Floorball',
        ]);
    }
}
