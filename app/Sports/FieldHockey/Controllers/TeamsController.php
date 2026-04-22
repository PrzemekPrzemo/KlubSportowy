<?php

namespace App\Sports\FieldHockey\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\FieldHockey\Models\FieldHockeyTeamModel;

class TeamsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('fieldhockey');
    }

    public function index(): void
    {
        $model = new FieldHockeyTeamModel();
        $teams = $model->listForClub();
        $rosters = [];
        foreach ($teams as $t) $rosters[$t['id']] = $model->roster((int)$t['id']);

        $this->render('fieldhockey/teams/index', [
            'title'      => 'Drużyny — Hokej na trawie',
            'teams'      => $teams,
            'rosters'    => $rosters,
            'members'    => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'categories' => FieldHockeyTeamModel::$CATEGORIES,
            'positions'  => FieldHockeyTeamModel::$POSITIONS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { Session::flash('error', 'Podaj nazwę.'); $this->redirect('fieldhockey/teams'); }
        $cat = array_key_exists($_POST['category'] ?? '', FieldHockeyTeamModel::$CATEGORIES) ? $_POST['category'] : 'senior_m';

        (new FieldHockeyTeamModel())->insert([
            'name'     => $name,
            'category' => $cat,
            'coach_id' => !empty($_POST['coach_id']) ? (int)$_POST['coach_id'] : null,
        ]);
        Session::flash('success', 'Drużyna utworzona.');
        $this->redirect('fieldhockey/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FieldHockeyTeamModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('fieldhockey/teams');
    }

    public function addPlayer(string $teamId): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $tid = (int)$teamId;
        if ($memberId <= 0 || $tid <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('fieldhockey/teams'); }
        $pos = array_key_exists($_POST['position'] ?? '', FieldHockeyTeamModel::$POSITIONS) ? $_POST['position'] : 'uniwersalny';

        Database::pdo()->prepare(
            "INSERT IGNORE INTO field_hockey_players (club_id, team_id, member_id, jersey_number, position, is_captain)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $this->currentClub(), $tid, $memberId,
            !empty($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null,
            $pos, isset($_POST['is_captain']) ? 1 : 0,
        ]);
        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('fieldhockey/teams');
    }

    public function removePlayer(string $id): void
    {
        Csrf::verify();
        Database::pdo()->prepare("DELETE FROM field_hockey_players WHERE id = ? AND club_id = ?")
            ->execute([(int)$id, $this->currentClub()]);
        Session::flash('success', 'Usunięto.');
        $this->redirect('fieldhockey/teams');
    }
}
