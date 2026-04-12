<?php

namespace App\Sports\Volleyball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Volleyball\Models\VolleyballMatchModel;
use App\Sports\Volleyball\Models\VolleyballTeamModel;

class MatchesController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? '';
        $pagination = (new VolleyballMatchModel())->listForClub($status ?: null, $page, 20);
        $this->render('volleyball/matches/index', [
            'title' => 'Mecze siatkówki', 'pagination' => $pagination, 'statusFilter' => $status,
        ]);
    }

    public function create(): void
    {
        $teams = (new VolleyballTeamModel())->listForClub();
        $this->render('volleyball/matches/form', ['title' => 'Nowy mecz', 'match' => null, 'teams' => $teams]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['created_by'] = Auth::id();
        (new VolleyballMatchModel())->insert($data);
        Session::flash('success', 'Mecz dodany.');
        $this->redirect('volleyball/matches');
    }

    public function show(string $id): void
    {
        $match = (new VolleyballMatchModel())->withDetails((int)$id);
        if (!$match) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('volleyball/matches'); }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('volleyball/matches/show', [
            'title' => $match['home_team_name'] . ' vs ' . $match['away_team'],
            'match' => $match, 'members' => $members,
        ]);
    }

    public function edit(string $id): void
    {
        $match = (new VolleyballMatchModel())->findById((int)$id);
        if (!$match) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('volleyball/matches'); }
        $teams = (new VolleyballTeamModel())->listForClub();
        $this->render('volleyball/matches/form', ['title' => 'Edycja meczu', 'match' => $match, 'teams' => $teams]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new VolleyballMatchModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('volleyball/matches/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new VolleyballMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('volleyball/matches');
    }

    public function addStats(string $id): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('volleyball/matches/' . $id);
            return;
        }

        $data = [
            'match_id'    => (int)$id,
            'member_id'   => $memberId,
            'attacks'     => $_POST['attacks'] !== '' ? (int)$_POST['attacks'] : null,
            'kills'       => $_POST['kills'] !== '' ? (int)$_POST['kills'] : null,
            'blocks'      => $_POST['blocks'] !== '' ? (int)$_POST['blocks'] : null,
            'serves'      => $_POST['serves'] !== '' ? (int)$_POST['serves'] : null,
            'aces'        => $_POST['aces'] !== '' ? (int)$_POST['aces'] : null,
            'digs'        => $_POST['digs'] !== '' ? (int)$_POST['digs'] : null,
            'errors'      => $_POST['errors'] !== '' ? (int)$_POST['errors'] : null,
            'sets_played' => $_POST['sets_played'] !== '' ? (int)$_POST['sets_played'] : null,
        ];

        $db = Database::pdo();
        $cols = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO volleyball_player_stats (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));

        Session::flash('success', 'Statystyki zawodnika dodane.');
        $this->redirect('volleyball/matches/' . $id);
    }

    private function parsePost(): ?array
    {
        $data = [
            'home_team_id' => (int)($_POST['home_team_id'] ?? 0),
            'away_team'    => trim($_POST['away_team'] ?? ''),
            'match_date'   => str_replace('T', ' ', trim($_POST['match_date'] ?? '')),
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'set1_home'    => ($_POST['set1_home'] ?? '') !== '' ? (int)$_POST['set1_home'] : null,
            'set1_away'    => ($_POST['set1_away'] ?? '') !== '' ? (int)$_POST['set1_away'] : null,
            'set2_home'    => ($_POST['set2_home'] ?? '') !== '' ? (int)$_POST['set2_home'] : null,
            'set2_away'    => ($_POST['set2_away'] ?? '') !== '' ? (int)$_POST['set2_away'] : null,
            'set3_home'    => ($_POST['set3_home'] ?? '') !== '' ? (int)$_POST['set3_home'] : null,
            'set3_away'    => ($_POST['set3_away'] ?? '') !== '' ? (int)$_POST['set3_away'] : null,
            'set4_home'    => ($_POST['set4_home'] ?? '') !== '' ? (int)$_POST['set4_home'] : null,
            'set4_away'    => ($_POST['set4_away'] ?? '') !== '' ? (int)$_POST['set4_away'] : null,
            'set5_home'    => ($_POST['set5_home'] ?? '') !== '' ? (int)$_POST['set5_home'] : null,
            'set5_away'    => ($_POST['set5_away'] ?? '') !== '' ? (int)$_POST['set5_away'] : null,
            'home_sets'    => ($_POST['home_sets'] ?? '') !== '' ? (int)$_POST['home_sets'] : null,
            'away_sets'    => ($_POST['away_sets'] ?? '') !== '' ? (int)$_POST['away_sets'] : null,
            'home_score'   => ($_POST['home_score'] ?? '') !== '' ? (int)$_POST['home_score'] : null,
            'away_score'   => ($_POST['away_score'] ?? '') !== '' ? (int)$_POST['away_score'] : null,
            'referee'      => trim($_POST['referee'] ?? '') ?: null,
            'match_type'   => in_array($_POST['match_type'] ?? '', ['ligowy','pucharowy','towarzyski','turniejowy'], true) ? $_POST['match_type'] : 'ligowy',
            'status'       => in_array($_POST['status'] ?? '', ['zaplanowany','w_trakcie','zakonczony','odwolany'], true) ? $_POST['status'] : 'zaplanowany',
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['home_team_id'] <= 0 || $data['away_team'] === '' || $data['match_date'] === '') {
            Session::flash('error', 'Drużyna i data meczu wymagane.');
            $this->redirect('volleyball/matches/create');
            return null;
        }
        return $data;
    }
}
