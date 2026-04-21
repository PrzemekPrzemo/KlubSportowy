<?php

namespace App\Sports\Handball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Handball\Models\HandballMatchModel;
use App\Sports\Handball\Models\HandballTeamModel;

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
        $teamId = !empty($_GET['team']) ? (int)$_GET['team'] : null;
        $status = $_GET['status'] ?? null;

        $mModel = new HandballMatchModel();
        $tModel = new HandballTeamModel();

        $this->render('handball/matches/index', [
            'title'    => 'Mecze piłki ręcznej',
            'matches'  => $mModel->listForClub($teamId, $status),
            'teams'    => $tModel->listForClub(),
            'statuses' => HandballMatchModel::$STATUSES,
            'teamFilter'   => $teamId,
            'statusFilter' => $status,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $homeTeam = (int)($_POST['home_team_id'] ?? 0);
        if ($homeTeam <= 0) {
            Session::flash('error', 'Wybierz drużynę gospodarzy.');
            $this->redirect('handball/matches');
        }

        $status = array_key_exists($_POST['status'] ?? '', HandballMatchModel::$STATUSES)
            ? $_POST['status'] : 'zaplanowany';

        (new HandballMatchModel())->insert([
            'home_team_id'   => $homeTeam,
            'away_team_id'   => !empty($_POST['away_team_id']) ? (int)$_POST['away_team_id'] : null,
            'away_team_name' => trim($_POST['away_team_name'] ?? '') ?: null,
            'match_date'     => trim($_POST['match_date'] ?? '') ?: date('Y-m-d H:i:s'),
            'location'       => trim($_POST['location'] ?? '') ?: null,
            'home_score'     => max(0, (int)($_POST['home_score'] ?? 0)),
            'away_score'     => max(0, (int)($_POST['away_score'] ?? 0)),
            'ht_home'        => max(0, (int)($_POST['ht_home'] ?? 0)),
            'ht_away'        => max(0, (int)($_POST['ht_away'] ?? 0)),
            'status'         => $status,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Mecz zapisany.');
        $this->redirect('handball/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new HandballMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('handball/matches');
    }
}
