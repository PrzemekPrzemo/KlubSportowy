<?php

namespace App\Sports\Basketball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Basketball\Models\BasketballMatchModel;
use App\Sports\Basketball\Models\BasketballPlayerStatsModel;
use App\Sports\Basketball\Models\BasketballTeamModel;

class MatchesController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? '';
        $pagination = (new BasketballMatchModel())->listForClub($status ?: null, $page, 20);
        $this->render('basketball/matches/index', [
            'title' => 'Mecze koszykówki', 'pagination' => $pagination, 'statusFilter' => $status,
        ]);
    }

    public function create(): void
    {
        $teams = (new BasketballTeamModel())->listForClub();
        $this->render('basketball/matches/form', ['title' => 'Nowy mecz', 'match' => null, 'teams' => $teams]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['created_by'] = Auth::id();
        (new BasketballMatchModel())->insert($data);
        Session::flash('success', 'Mecz dodany.');
        $this->redirect('basketball/matches');
    }

    public function show(string $id): void
    {
        $match = (new BasketballMatchModel())->withDetails((int)$id);
        if (!$match) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('basketball/matches'); }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('basketball/matches/show', [
            'title' => $match['home_team_name'] . ' vs ' . $match['away_team'],
            'match' => $match, 'members' => $members,
        ]);
    }

    public function edit(string $id): void
    {
        $match = (new BasketballMatchModel())->findById((int)$id);
        if (!$match) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('basketball/matches'); }
        $teams = (new BasketballTeamModel())->listForClub();
        $this->render('basketball/matches/form', ['title' => 'Edycja meczu', 'match' => $match, 'teams' => $teams]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new BasketballMatchModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('basketball/matches/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BasketballMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('basketball/matches');
    }

    public function addStats(string $id): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('basketball/matches/' . $id); }

        $data = [
            'match_id'            => (int)$id,
            'member_id'           => $memberId,
            'minutes'             => !empty($_POST['minutes']) ? (int)$_POST['minutes'] : null,
            'points'              => !empty($_POST['points']) ? (int)$_POST['points'] : null,
            'assists'             => !empty($_POST['assists']) ? (int)$_POST['assists'] : null,
            'rebounds'            => !empty($_POST['rebounds']) ? (int)$_POST['rebounds'] : null,
            'steals'              => !empty($_POST['steals']) ? (int)$_POST['steals'] : null,
            'blocks'              => !empty($_POST['blocks']) ? (int)$_POST['blocks'] : null,
            'turnovers'           => !empty($_POST['turnovers']) ? (int)$_POST['turnovers'] : null,
            'fouls'               => !empty($_POST['fouls']) ? (int)$_POST['fouls'] : null,
            'three_pointers'      => !empty($_POST['three_pointers']) ? (int)$_POST['three_pointers'] : null,
            'free_throws_made'    => !empty($_POST['free_throws_made']) ? (int)$_POST['free_throws_made'] : null,
            'free_throws_attempts' => !empty($_POST['free_throws_attempts']) ? (int)$_POST['free_throws_attempts'] : null,
        ];

        $db = Database::pdo();
        // Upsert — replace if already exists for this match+member
        $db->prepare(
            "DELETE FROM basketball_player_stats WHERE match_id = ? AND member_id = ?"
        )->execute([(int)$id, $memberId]);

        $cols = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO basketball_player_stats (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));

        Session::flash('success', 'Statystyki zawodnika zapisane.');
        $this->redirect('basketball/matches/' . $id);
    }

    private function parsePost(): ?array
    {
        $data = [
            'home_team_id' => (int)($_POST['home_team_id'] ?? 0),
            'away_team'    => trim($_POST['away_team'] ?? ''),
            'match_date'   => str_replace('T', ' ', trim($_POST['match_date'] ?? '')),
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'q1_home'      => $_POST['q1_home'] !== '' ? (int)$_POST['q1_home'] : null,
            'q1_away'      => $_POST['q1_away'] !== '' ? (int)$_POST['q1_away'] : null,
            'q2_home'      => $_POST['q2_home'] !== '' ? (int)$_POST['q2_home'] : null,
            'q2_away'      => $_POST['q2_away'] !== '' ? (int)$_POST['q2_away'] : null,
            'q3_home'      => $_POST['q3_home'] !== '' ? (int)$_POST['q3_home'] : null,
            'q3_away'      => $_POST['q3_away'] !== '' ? (int)$_POST['q3_away'] : null,
            'q4_home'      => $_POST['q4_home'] !== '' ? (int)$_POST['q4_home'] : null,
            'q4_away'      => $_POST['q4_away'] !== '' ? (int)$_POST['q4_away'] : null,
            'overtime_home' => isset($_POST['overtime_home']) && $_POST['overtime_home'] !== '' ? (int)$_POST['overtime_home'] : null,
            'overtime_away' => isset($_POST['overtime_away']) && $_POST['overtime_away'] !== '' ? (int)$_POST['overtime_away'] : null,
            'home_score'   => isset($_POST['home_score']) && $_POST['home_score'] !== '' ? (int)$_POST['home_score'] : null,
            'away_score'   => isset($_POST['away_score']) && $_POST['away_score'] !== '' ? (int)$_POST['away_score'] : null,
            'referee'      => trim($_POST['referee'] ?? '') ?: null,
            'match_type'   => in_array($_POST['match_type'] ?? '', ['ligowy','pucharowy','towarzyski','turniejowy'], true) ? $_POST['match_type'] : 'ligowy',
            'status'       => in_array($_POST['status'] ?? '', ['zaplanowany','w_trakcie','zakonczony','odwolany'], true) ? $_POST['status'] : 'zaplanowany',
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['home_team_id'] <= 0 || $data['away_team'] === '' || $data['match_date'] === '') {
            Session::flash('error', 'Drużyna i data meczu wymagane.');
            $this->redirect('basketball/matches/create');
            return null;
        }
        return $data;
    }
}
