<?php

namespace App\Sports\Football\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Football\Models\FootballLeagueModel;
use App\Sports\Football\Models\FootballTeamModel;

class LeaguesController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $leagues = (new FootballLeagueModel())->listForClub();
        $this->render('football/leagues/index', ['title' => 'Ligi', 'leagues' => $leagues]);
    }

    public function create(): void
    {
        $teams = (new FootballTeamModel())->listForClub();
        $this->render('football/leagues/create', ['title' => 'Nowa liga', 'teams' => $teams]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name   = trim($_POST['name'] ?? '');
        $season = trim($_POST['season'] ?? '');
        if ($name === '' || $season === '') {
            Session::flash('error', 'Uzupełnij nazwę i sezon.');
            $this->redirect('football/leagues/create');
        }
        $model = new FootballLeagueModel();
        $leagueId = $model->insert([
            'name'       => $name,
            'season'     => $season,
            'start_date' => trim($_POST['start_date'] ?? '') ?: null,
            'end_date'   => trim($_POST['end_date'] ?? '') ?: null,
        ]);

        // Add selected teams
        $teamIds = $_POST['team_ids'] ?? [];
        foreach ($teamIds as $tid) {
            $tid = (int)$tid;
            if ($tid > 0) {
                $model->addTeamToLeague($leagueId, $tid);
            }
        }

        Session::flash('success', 'Liga została dodana.');
        $this->redirect('football/leagues');
    }

    public function show(string $id): void
    {
        $model  = new FootballLeagueModel();
        $league = $model->findById((int)$id);
        if (!$league) {
            Session::flash('error', 'Liga nie istnieje.');
            $this->redirect('football/leagues');
        }
        $model->recalculateStandings((int)$id);
        $standings = $model->standingsForLeague((int)$id);
        $this->render('football/leagues/show', [
            'title'     => 'Tabela: ' . $league['name'],
            'league'    => $league,
            'standings' => $standings,
        ]);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FootballLeagueModel())->delete((int)$id);
        Session::flash('success', 'Liga została usunięta.');
        $this->redirect('football/leagues');
    }
}
