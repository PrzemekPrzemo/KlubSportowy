<?php

namespace App\Sports\WaterPolo\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\WaterPolo\Models\WaterPoloTeamModel;

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
        $teamModel = new WaterPoloTeamModel();
        $teams     = $teamModel->listForClub();
        $rosters   = [];
        foreach ($teams as $t) {
            $rosters[$t['id']] = $teamModel->roster((int)$t['id']);
        }
        $this->render('water_polo/teams/index', [
            'title'   => 'Drużyny — Piłka wodna',
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
            $this->redirect('water_polo/teams');
        }
        $allowed = ['senior_m','senior_k','junior_m','junior_k','U18','U16','U14','dzieci'];
        $cat     = in_array($_POST['category'] ?? '', $allowed, true) ? $_POST['category'] : 'senior_m';
        (new WaterPoloTeamModel())->insert([
            'name'     => $name,
            'category' => $cat,
        ]);
        Session::flash('success', 'Drużyna zapisana.');
        $this->redirect('water_polo/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new WaterPoloTeamModel())->delete((int)$id);
        Session::flash('success', 'Drużyna usunięta.');
        $this->redirect('water_polo/teams');
    }

    public function addPlayer(string $id): void
    {
        Csrf::verify();
        $teamId   = (int)$id;
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz członka klubu.');
            $this->redirect('water_polo/teams');
        }
        (new WaterPoloTeamModel())->addPlayer($teamId, $memberId, [
            'cap_number' => isset($_POST['cap_number']) ? (int)$_POST['cap_number'] : null,
            'position'   => $_POST['position'] ?? 'uniwersalny',
            'is_captain' => !empty($_POST['is_captain']),
        ]);
        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('water_polo/teams');
    }

    public function removePlayer(string $playerId): void
    {
        Csrf::verify();
        (new WaterPoloTeamModel())->removePlayer((int)$playerId);
        Session::flash('success', 'Zawodnik usunięty.');
        $this->redirect('water_polo/teams');
    }
}
