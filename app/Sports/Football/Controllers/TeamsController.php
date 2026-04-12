<?php

namespace App\Sports\Football\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Football\Models\FootballTeamModel;

class TeamsController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $teams = (new FootballTeamModel())->listForClub();
        $this->render('football/teams/index', ['title' => 'Drużyny piłkarskie', 'teams' => $teams]);
    }

    public function create(): void
    {
        $this->render('football/teams/form', ['title' => 'Nowa drużyna', 'team' => null]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'league'          => trim($_POST['league'] ?? '') ?: null,
            'age_category_id' => !empty($_POST['age_category_id']) ? (int)$_POST['age_category_id'] : null,
        ];
        if ($data['name'] === '') { Session::flash('error', 'Nazwa wymagana.'); $this->redirect('football/teams/create'); }
        (new FootballTeamModel())->insert($data);
        Session::flash('success', 'Drużyna dodana.');
        $this->redirect('football/teams');
    }

    public function edit(string $id): void
    {
        $team = (new FootballTeamModel())->findById((int)$id);
        if (!$team) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('football/teams'); }
        $this->render('football/teams/form', ['title' => 'Edycja drużyny', 'team' => $team]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = [
            'name'   => trim($_POST['name'] ?? ''),
            'league' => trim($_POST['league'] ?? '') ?: null,
            'age_category_id' => !empty($_POST['age_category_id']) ? (int)$_POST['age_category_id'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        (new FootballTeamModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('football/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FootballTeamModel())->delete((int)$id);
        Session::flash('success', 'Drużyna usunięta.');
        $this->redirect('football/teams');
    }
}
