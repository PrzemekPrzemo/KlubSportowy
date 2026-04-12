<?php

namespace App\Sports\Volleyball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Volleyball\Models\VolleyballTeamModel;

class TeamsController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $teams = (new VolleyballTeamModel())->listForClub();
        $this->render('volleyball/teams/index', ['title' => 'Drużyny siatkarskie', 'teams' => $teams]);
    }

    public function create(): void
    {
        $this->render('volleyball/teams/form', ['title' => 'Nowa drużyna', 'team' => null]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'league'          => trim($_POST['league'] ?? '') ?: null,
            'age_category_id' => !empty($_POST['age_category_id']) ? (int)$_POST['age_category_id'] : null,
            'coach_id'        => !empty($_POST['coach_id']) ? (int)$_POST['coach_id'] : null,
        ];
        if ($data['name'] === '') { Session::flash('error', 'Nazwa wymagana.'); $this->redirect('volleyball/teams/create'); }
        (new VolleyballTeamModel())->insert($data);
        Session::flash('success', 'Drużyna dodana.');
        $this->redirect('volleyball/teams');
    }

    public function edit(string $id): void
    {
        $team = (new VolleyballTeamModel())->findById((int)$id);
        if (!$team) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('volleyball/teams'); }
        $this->render('volleyball/teams/form', ['title' => 'Edycja drużyny', 'team' => $team]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'league'          => trim($_POST['league'] ?? '') ?: null,
            'age_category_id' => !empty($_POST['age_category_id']) ? (int)$_POST['age_category_id'] : null,
            'coach_id'        => !empty($_POST['coach_id']) ? (int)$_POST['coach_id'] : null,
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ];
        (new VolleyballTeamModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('volleyball/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new VolleyballTeamModel())->delete((int)$id);
        Session::flash('success', 'Drużyna usunięta.');
        $this->redirect('volleyball/teams');
    }
}
