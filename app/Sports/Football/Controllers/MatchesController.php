<?php

namespace App\Sports\Football\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Football\Models\FootballMatchModel;
use App\Sports\Football\Models\FootballTeamModel;

class MatchesController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? '';
        $pagination = (new FootballMatchModel())->listForClub($status ?: null, $page, 20);
        $this->render('football/matches/index', [
            'title' => 'Mecze', 'pagination' => $pagination, 'statusFilter' => $status,
        ]);
    }

    public function create(): void
    {
        $teams = (new FootballTeamModel())->listForClub();
        $this->render('football/matches/form', ['title' => 'Nowy mecz', 'match' => null, 'teams' => $teams]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['created_by'] = Auth::id();
        (new FootballMatchModel())->insert($data);
        Session::flash('success', 'Mecz dodany.');
        $this->redirect('football/matches');
    }

    public function show(string $id): void
    {
        $match = (new FootballMatchModel())->withDetails((int)$id);
        if (!$match) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('football/matches'); }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('football/matches/show', [
            'title' => $match['home_team_name'] . ' vs ' . $match['away_team'],
            'match' => $match, 'members' => $members,
        ]);
    }

    public function edit(string $id): void
    {
        $match = (new FootballMatchModel())->findById((int)$id);
        if (!$match) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('football/matches'); }
        $teams = (new FootballTeamModel())->listForClub();
        $this->render('football/matches/form', ['title' => 'Edycja meczu', 'match' => $match, 'teams' => $teams]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new FootballMatchModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('football/matches/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FootballMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('football/matches');
    }

    public function addEvent(string $id): void
    {
        Csrf::verify();
        $data = [
            'match_id'  => (int)$id,
            'member_id' => (int)($_POST['member_id'] ?? 0),
            'minute'    => !empty($_POST['minute']) ? (int)$_POST['minute'] : null,
            'type'      => $_POST['type'] ?? 'gol',
            'notes'     => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['member_id'] <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('football/matches/' . $id); }
        $db = Database::pdo();
        $cols = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO football_match_events (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        Session::flash('success', 'Wydarzenie dodane.');
        $this->redirect('football/matches/' . $id);
    }

    public function addLineup(string $id): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $teamId   = (int)($_POST['team_id'] ?? 0);
        if ($memberId <= 0 || $teamId <= 0) { Session::flash('error', 'Wybierz zawodnika i drużynę.'); $this->redirect('football/matches/' . $id); }
        $db = Database::pdo();
        $db->prepare(
            "INSERT IGNORE INTO football_lineups (match_id, member_id, team_id, position, is_starter, jersey_number)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            (int)$id, $memberId, $teamId,
            $_POST['position'] ?? null,
            isset($_POST['is_starter']) ? 1 : 0,
            !empty($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null,
        ]);
        Session::flash('success', 'Zawodnik dodany do składu.');
        $this->redirect('football/matches/' . $id);
    }

    private function parsePost(): ?array
    {
        $data = [
            'home_team_id' => (int)($_POST['home_team_id'] ?? 0),
            'away_team'    => trim($_POST['away_team'] ?? ''),
            'match_date'   => str_replace('T', ' ', trim($_POST['match_date'] ?? '')),
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'home_score'   => $_POST['home_score'] !== '' ? (int)$_POST['home_score'] : null,
            'away_score'   => $_POST['away_score'] !== '' ? (int)$_POST['away_score'] : null,
            'referee'      => trim($_POST['referee'] ?? '') ?: null,
            'league_round' => trim($_POST['league_round'] ?? '') ?: null,
            'match_type'   => in_array($_POST['match_type'] ?? '', ['ligowy','pucharowy','towarzyski','turniejowy'], true) ? $_POST['match_type'] : 'ligowy',
            'status'       => in_array($_POST['status'] ?? '', ['zaplanowany','w_trakcie','zakonczony','odwolany'], true) ? $_POST['status'] : 'zaplanowany',
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['home_team_id'] <= 0 || $data['away_team'] === '' || $data['match_date'] === '') {
            Session::flash('error', 'Drużyna i data meczu wymagane.');
            $this->redirect('football/matches/create');
            return null;
        }
        return $data;
    }
}
