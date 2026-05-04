<?php

namespace App\Sports\Floorball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Floorball\Models\FloorballTeamModel;

class TeamsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $teamModel = new FloorballTeamModel();
        $teams     = $teamModel->listForClub();
        $rosters   = [];
        foreach ($teams as $t) {
            $rosters[$t['id']] = $teamModel->roster((int)$t['id']);
        }

        $this->render('floorball/teams/index', [
            'title'   => 'Drużyny — Floorball',
            'teams'   => $teams,
            'rosters' => $rosters,
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Podaj nazwę drużyny.');
            $this->redirect('floorball/teams');
        }
        (new FloorballTeamModel())->insert([
            'name'      => $name,
            'age_group' => trim($_POST['age_group'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Drużyna dodana.');
        $this->redirect('floorball/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FloorballTeamModel())->delete((int)$id);
        Session::flash('success', 'Usunięto drużynę.');
        $this->redirect('floorball/teams');
    }

    public function addPlayer(string $id): void
    {
        Csrf::verify();
        $teamId   = (int)$id;
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('floorball/teams');
        }
        (new FloorballTeamModel())->addPlayer($teamId, $memberId, [
            'jersey_number' => !empty($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null,
            'position'      => $_POST['position'] ?? 'napastnik',
        ]);
        Session::flash('success', 'Zawodnik dodany do drużyny.');
        $this->redirect('floorball/teams');
    }

    public function removePlayer(string $id): void
    {
        Csrf::verify();
        $teamId   = (int)$id;
        $memberId = (int)($_POST['member_id'] ?? 0);
        (new FloorballTeamModel())->removePlayer($teamId, $memberId);
        Session::flash('success', 'Zawodnik usunięty z drużyny.');
        $this->redirect('floorball/teams');
    }
}
