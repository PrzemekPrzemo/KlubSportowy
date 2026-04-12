<?php

namespace App\Sports\Basketball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Basketball\Models\BasketballTeamModel;

class TeamsController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $teams = (new BasketballTeamModel())->listForClub();
        $this->render('basketball/teams/index', ['title' => 'Drużyny koszykarskie', 'teams' => $teams]);
    }

    public function create(): void
    {
        $this->render('basketball/teams/form', ['title' => 'Nowa drużyna', 'team' => null]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'league'          => trim($_POST['league'] ?? '') ?: null,
            'age_category_id' => !empty($_POST['age_category_id']) ? (int)$_POST['age_category_id'] : null,
        ];
        if ($data['name'] === '') { Session::flash('error', 'Nazwa wymagana.'); $this->redirect('basketball/teams/create'); }
        (new BasketballTeamModel())->insert($data);
        Session::flash('success', 'Drużyna dodana.');
        $this->redirect('basketball/teams');
    }

    public function edit(string $id): void
    {
        $team = (new BasketballTeamModel())->findById((int)$id);
        if (!$team) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('basketball/teams'); }
        $this->render('basketball/teams/form', ['title' => 'Edycja drużyny', 'team' => $team]);
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
        (new BasketballTeamModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('basketball/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BasketballTeamModel())->delete((int)$id);
        Session::flash('success', 'Drużyna usunięta.');
        $this->redirect('basketball/teams');
    }
}
