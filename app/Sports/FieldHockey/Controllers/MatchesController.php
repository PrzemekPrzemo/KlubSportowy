<?php

namespace App\Sports\FieldHockey\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\FieldHockey\Models\FieldHockeyMatchModel;
use App\Sports\FieldHockey\Models\FieldHockeyTeamModel;

class MatchesController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('fieldhockey');
    }

    public function index(): void
    {
        $teamId = !empty($_GET['team']) ? (int)$_GET['team'] : null;
        $mModel = new FieldHockeyMatchModel();
        $tModel = new FieldHockeyTeamModel();
        $this->render('fieldhockey/matches/index', [
            'title'      => 'Mecze — Hokej na trawie',
            'matches'    => $mModel->listForClub($teamId),
            'teams'      => $tModel->listForClub(),
            'statuses'   => FieldHockeyMatchModel::$STATUSES,
            'teamFilter' => $teamId,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $home = (int)($_POST['home_team_id'] ?? 0);
        if ($home <= 0) { Session::flash('error', 'Wybierz drużynę.'); $this->redirect('fieldhockey/matches'); }
        $status = array_key_exists($_POST['status'] ?? '', FieldHockeyMatchModel::$STATUSES) ? $_POST['status'] : 'zaplanowany';

        (new FieldHockeyMatchModel())->insert([
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
        $this->redirect('fieldhockey/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FieldHockeyMatchModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('fieldhockey/matches');
    }
}
