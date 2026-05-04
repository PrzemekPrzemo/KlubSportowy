<?php

namespace App\Sports\Floorball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Sports\Floorball\Models\FloorballMatchModel;
use App\Sports\Floorball\Models\FloorballTeamModel;

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
        $matchModel = new FloorballMatchModel();
        $teams      = (new FloorballTeamModel())->listForClub();
        $matches    = $matchModel->schedule();

        $scorers = [];
        foreach ($teams as $t) {
            $scorers[$t['id']] = $matchModel->topScorers((int)$t['id'], 5);
        }

        $this->render('floorball/matches/index', [
            'title'   => 'Mecze — Floorball',
            'matches' => $matches,
            'teams'   => $teams,
            'scorers' => $scorers,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $matchDate = trim($_POST['match_date'] ?? '');
        if (!$matchDate) {
            Session::flash('error', 'Podaj datę meczu.');
            $this->redirect('floorball/matches');
        }
        (new FloorballMatchModel())->insert([
            'home_team_id' => !empty($_POST['home_team_id']) ? (int)$_POST['home_team_id'] : null,
            'away_team_id' => !empty($_POST['away_team_id']) ? (int)$_POST['away_team_id'] : null,
            'match_date'   => $matchDate,
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'status'       => 'zaplanowany',
        ]);
        Session::flash('success', 'Mecz zaplanowany.');
        $this->redirect('floorball/matches');
    }

    public function saveResult(string $id): void
    {
        Csrf::verify();
        $matchId    = (int)$id;
        $matchModel = new FloorballMatchModel();
        Database::pdo()->prepare(
            "UPDATE floorball_matches SET home_score=?, away_score=?, status='zakonczony'
             WHERE id=? AND club_id=?"
        )->execute([
            (int)($_POST['home_score'] ?? 0),
            (int)($_POST['away_score'] ?? 0),
            $matchId,
            $matchModel->clubId(),
        ]);
        Session::flash('success', 'Wynik zapisany.');
        $this->redirect('floorball/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FloorballMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('floorball/matches');
    }
}
