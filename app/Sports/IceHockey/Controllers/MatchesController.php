<?php

namespace App\Sports\IceHockey\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\IceHockey\Models\IceHockeyMatchModel;
use App\Sports\IceHockey\Models\IceHockeyTeamModel;

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
        $mModel = new IceHockeyMatchModel();
        $tModel = new IceHockeyTeamModel();

        $this->render('icehockey/matches/index', [
            'title'      => 'Mecze hokeja',
            'matches'    => $mModel->listForClub($teamId),
            'teams'      => $tModel->listForClub(),
            'statuses'   => IceHockeyMatchModel::$STATUSES,
            'teamFilter' => $teamId,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $homeTeam = (int)($_POST['home_team_id'] ?? 0);
        if ($homeTeam <= 0) { Session::flash('error', 'Wybierz drużynę.'); $this->redirect('icehockey/matches'); }

        $status = array_key_exists($_POST['status'] ?? '', IceHockeyMatchModel::$STATUSES) ? $_POST['status'] : 'zaplanowany';

        (new IceHockeyMatchModel())->insert([
            'home_team_id'   => $homeTeam,
            'away_team_name' => trim($_POST['away_team_name'] ?? '') ?: null,
            'match_date'     => trim($_POST['match_date'] ?? '') ?: date('Y-m-d H:i:s'),
            'arena'          => trim($_POST['arena'] ?? '') ?: null,
            'p1_home' => max(0, (int)($_POST['p1_home'] ?? 0)),
            'p1_away' => max(0, (int)($_POST['p1_away'] ?? 0)),
            'p2_home' => max(0, (int)($_POST['p2_home'] ?? 0)),
            'p2_away' => max(0, (int)($_POST['p2_away'] ?? 0)),
            'p3_home' => max(0, (int)($_POST['p3_home'] ?? 0)),
            'p3_away' => max(0, (int)($_POST['p3_away'] ?? 0)),
            'ot_home' => max(0, (int)($_POST['ot_home'] ?? 0)),
            'ot_away' => max(0, (int)($_POST['ot_away'] ?? 0)),
            'so_home' => max(0, (int)($_POST['so_home'] ?? 0)),
            'so_away' => max(0, (int)($_POST['so_away'] ?? 0)),
            'shootout'=> isset($_POST['shootout']) ? 1 : 0,
            'status'  => $status,
            'notes'   => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Mecz zapisany.');
        $this->redirect('icehockey/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new IceHockeyMatchModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('icehockey/matches');
    }
}
