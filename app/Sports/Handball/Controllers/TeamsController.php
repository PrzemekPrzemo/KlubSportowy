<?php

namespace App\Sports\Handball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Handball\Models\HandballTeamModel;

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
        $model = new HandballTeamModel();
        $teams = $model->listForClub();

        $rosters = [];
        foreach ($teams as $t) {
            $rosters[$t['id']] = $model->roster((int)$t['id']);
        }

        $this->render('handball/teams/index', [
            'title'      => 'Drużyny piłki ręcznej',
            'teams'      => $teams,
            'rosters'    => $rosters,
            'members'    => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'categories' => HandballTeamModel::$CATEGORIES,
            'positions'  => HandballTeamModel::$POSITIONS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Podaj nazwę drużyny.');
            $this->redirect('handball/teams');
        }
        $category = array_key_exists($_POST['category'] ?? '', HandballTeamModel::$CATEGORIES)
            ? $_POST['category'] : 'senior_m';

        (new HandballTeamModel())->insert([
            'name'      => $name,
            'age_group' => trim($_POST['age_group'] ?? '') ?: null,
            'category'  => $category,
            'coach_id'  => !empty($_POST['coach_id']) ? (int)$_POST['coach_id'] : null,
        ]);
        Session::flash('success', 'Drużyna utworzona.');
        $this->redirect('handball/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new HandballTeamModel())->delete((int)$id);
        Session::flash('success', 'Drużyna usunięta.');
        $this->redirect('handball/teams');
    }

    public function addPlayer(string $teamId): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $tid      = (int)$teamId;
        if ($memberId <= 0 || $tid <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('handball/teams');
        }

        $position = array_key_exists($_POST['position'] ?? '', HandballTeamModel::$POSITIONS)
            ? $_POST['position'] : 'uniwersalny';

        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT IGNORE INTO handball_players (club_id, team_id, member_id, jersey_number, position, is_captain)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $this->currentClub(),
            $tid,
            $memberId,
            !empty($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null,
            $position,
            isset($_POST['is_captain']) ? 1 : 0,
        ]);
        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('handball/teams');
    }

    public function removePlayer(string $id): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $db->prepare("DELETE FROM handball_players WHERE id = ? AND club_id = ?")
           ->execute([(int)$id, $this->currentClub()]);
        Session::flash('success', 'Zawodnik usunięty z drużyny.');
        $this->redirect('handball/teams');
    }
}
