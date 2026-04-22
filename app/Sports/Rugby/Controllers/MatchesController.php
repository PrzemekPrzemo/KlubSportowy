<?php

namespace App\Sports\Rugby\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Rugby\Models\RugbyMatchModel;
use App\Sports\Rugby\Models\RugbyTeamModel;

class MatchesController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('rugby');
    }

    public function index(): void
    {
        $teamId = !empty($_GET['team']) ? (int)$_GET['team'] : null;
        $mModel = new RugbyMatchModel();
        $tModel = new RugbyTeamModel();
        $this->render('rugby/matches/index', [
            'title'      => 'Mecze rugby',
            'matches'    => $mModel->listForClub($teamId),
            'teams'      => $tModel->listForClub(),
            'statuses'   => RugbyMatchModel::$STATUSES,
            'formats'    => RugbyTeamModel::$FORMATS,
            'teamFilter' => $teamId,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $home = (int)($_POST['home_team_id'] ?? 0);
        if ($home <= 0) { Session::flash('error', 'Wybierz drużynę.'); $this->redirect('rugby/matches'); }
        $status = array_key_exists($_POST['status'] ?? '', RugbyMatchModel::$STATUSES) ? $_POST['status'] : 'zaplanowany';
        $format = array_key_exists($_POST['format'] ?? '', RugbyTeamModel::$FORMATS) ? $_POST['format'] : '15s';

        (new RugbyMatchModel())->insert([
            'home_team_id'   => $home,
            'away_team_name' => trim($_POST['away_team_name'] ?? '') ?: null,
            'match_date'     => trim($_POST['match_date'] ?? '') ?: date('Y-m-d H:i:s'),
            'location'       => trim($_POST['location'] ?? '') ?: null,
            'home_score'     => max(0, (int)($_POST['home_score'] ?? 0)),
            'away_score'     => max(0, (int)($_POST['away_score'] ?? 0)),
            'format'         => $format,
            'status'         => $status,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Mecz zapisany.');
        $this->redirect('rugby/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new RugbyMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('rugby/matches');
    }
}
