<?php

namespace App\Sports\IceHockey\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\IceHockey\Models\IceHockeyTeamModel;

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
        $model = new IceHockeyTeamModel();
        $teams = $model->listForClub();
        $rosters = [];
        foreach ($teams as $t) $rosters[$t['id']] = $model->roster((int)$t['id']);

        $this->render('icehockey/teams/index', [
            'title'     => 'Drużyny hokeja',
            'teams'     => $teams,
            'rosters'   => $rosters,
            'members'   => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'positions' => IceHockeyTeamModel::$POSITIONS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { Session::flash('error', 'Podaj nazwę.'); $this->redirect('icehockey/teams'); }

        (new IceHockeyTeamModel())->insert([
            'name'      => $name,
            'age_group' => trim($_POST['age_group'] ?? '') ?: null,
            'arena'     => trim($_POST['arena'] ?? '') ?: null,
            'coach_id'  => !empty($_POST['coach_id']) ? (int)$_POST['coach_id'] : null,
        ]);
        Session::flash('success', 'Drużyna utworzona.');
        $this->redirect('icehockey/teams');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new IceHockeyTeamModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('icehockey/teams');
    }

    public function addPlayer(string $teamId): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $tid      = (int)$teamId;
        if ($memberId <= 0 || $tid <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('icehockey/teams');
        }
        $position = array_key_exists($_POST['position'] ?? '', IceHockeyTeamModel::$POSITIONS)
            ? $_POST['position'] : 'napastnik';
        $shoots = in_array($_POST['shoots'] ?? '', ['prawy','lewy'], true) ? $_POST['shoots'] : 'prawy';

        Database::pdo()->prepare(
            "INSERT IGNORE INTO icehockey_players (club_id, team_id, member_id, jersey_number, position, shoots, is_captain, is_assistant)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $this->currentClub(),
            $tid,
            $memberId,
            !empty($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null,
            $position,
            $shoots,
            isset($_POST['is_captain']) ? 1 : 0,
            isset($_POST['is_assistant']) ? 1 : 0,
        ]);
        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('icehockey/teams');
    }

    public function removePlayer(string $id): void
    {
        Csrf::verify();
        Database::pdo()->prepare("DELETE FROM icehockey_players WHERE id = ? AND club_id = ?")
            ->execute([(int)$id, $this->currentClub()]);
        Session::flash('success', 'Usunięto z drużyny.');
        $this->redirect('icehockey/teams');
    }
}
