<?php

namespace App\Sports\Futsal\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Futsal\Models\FutsalTeamModel;

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
        $teamModel = new FutsalTeamModel();
        $teams     = $teamModel->listForClub();
        $rosters   = [];
        foreach ($teams as $t) {
            $rosters[$t['id']] = $teamModel->roster((int)$t['id']);
        }
        $this->render('futsal/teams/index', [
            'title'   => 'Drużyny — Futsal',
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
            $this->redirect('futsal/teams');
        }
        $allowed = ['senior_m','senior_k','junior_m','junior_k','U18','U16','U14','dzieci'];
        $cat     = in_array($_POST['category'] ?? '', $allowed, true) ? $_POST['category'] : 'senior_m';
        (new FutsalTeamModel())->insert([
            'name'      => $name,
            'category'  => $cat,
            'age_group' => trim($_POST['age_group'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Drużyna zapisana.');
        $this->redirect('futsal/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FutsalTeamModel())->delete((int)$id);
        Session::flash('success', 'Drużyna usunięta.');
        $this->redirect('futsal/teams');
    }

    public function addPlayer(string $id): void
    {
        Csrf::verify();
        $teamId   = (int)$id;
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz członka klubu.');
            $this->redirect('futsal/teams');
        }
        (new FutsalTeamModel())->addPlayer($teamId, $memberId, [
            'jersey_number' => isset($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null,
            'position'      => $_POST['position'] ?? 'uniwersalny',
            'is_captain'    => !empty($_POST['is_captain']),
        ]);
        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('futsal/teams');
    }

    public function removePlayer(string $playerId): void
    {
        Csrf::verify();
        (new FutsalTeamModel())->removePlayer((int)$playerId);
        Session::flash('success', 'Zawodnik usunięty.');
        $this->redirect('futsal/teams');
    }
}
