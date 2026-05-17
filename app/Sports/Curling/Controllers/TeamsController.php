<?php

namespace App\Sports\Curling\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Curling\Models\CurlingTeamModel;

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
        $teamModel = new CurlingTeamModel();
        $teams     = $teamModel->listForClub();
        $rosters   = [];
        foreach ($teams as $t) {
            $rosters[$t['id']] = $teamModel->roster((int)$t['id']);
        }
        $this->render('curling/teams/index', [
            'title'   => 'Drużyny — Curling',
            'teams'   => $teams,
            'rosters' => $rosters,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Podaj nazwę drużyny.');
            $this->redirect('curling/teams');
        }
        $allowed = ['senior_m','senior_k','mixed','mixed_doubles','wheelchair','junior'];
        $cat     = in_array($_POST['category'] ?? '', $allowed, true) ? $_POST['category'] : 'mixed';
        (new CurlingTeamModel())->insert([
            'name'     => $name,
            'category' => $cat,
        ]);
        Session::flash('success', 'Drużyna zapisana.');
        $this->redirect('curling/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new CurlingTeamModel())->delete((int)$id);
        Session::flash('success', 'Drużyna usunięta.');
        $this->redirect('curling/teams');
    }

    public function addPlayer(string $id): void
    {
        Csrf::verify();
        $teamId   = (int)$id;
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz członka klubu.');
            $this->redirect('curling/teams');
        }
        $allowed = ['skip','third','second','lead','alternate'];
        $pos     = in_array($_POST['position'] ?? '', $allowed, true) ? $_POST['position'] : 'lead';
        (new CurlingTeamModel())->addPlayer($teamId, $memberId, [
            'position'   => $pos,
            'is_captain' => !empty($_POST['is_captain']),
        ]);
        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('curling/teams');
    }

    public function removePlayer(string $playerId): void
    {
        Csrf::verify();
        (new CurlingTeamModel())->removePlayer((int)$playerId);
        Session::flash('success', 'Zawodnik usunięty.');
        $this->redirect('curling/teams');
    }
}
