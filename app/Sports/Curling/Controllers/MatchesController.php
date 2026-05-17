<?php

namespace App\Sports\Curling\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Curling\Models\CurlingMatchEndModel;
use App\Sports\Curling\Models\CurlingMatchModel;
use App\Sports\Curling\Models\CurlingTeamModel;

class MatchesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $mModel = new CurlingMatchModel();
        $tModel = new CurlingTeamModel();
        $this->render('curling/matches/index', [
            'title'    => 'Mecze — Curling',
            'matches'  => $mModel->listForClub(),
            'teams'    => $tModel->listForClub(),
            'statuses' => CurlingMatchModel::$STATUSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $home = (int)($_POST['home_team_id'] ?? 0);
        if ($home <= 0) {
            Session::flash('error', 'Wybierz drużynę gospodarzy.');
            $this->redirect('curling/matches');
        }
        $status = array_key_exists($_POST['status'] ?? '', CurlingMatchModel::$STATUSES)
            ? $_POST['status'] : 'zaplanowany';
        $hammer = ($_POST['hammer_start'] ?? '') === 'home' ? 'home' : 'away';
        $ends   = max(4, min(10, (int)($_POST['ends_planned'] ?? 8)));
        (new CurlingMatchModel())->insert([
            'home_team_id'   => $home,
            'away_team_name' => trim($_POST['away_team_name'] ?? '') ?: null,
            'match_date'     => trim($_POST['match_date'] ?? '') ?: date('Y-m-d H:i:s'),
            'location'       => trim($_POST['location'] ?? '') ?: null,
            'home_score'     => 0,
            'away_score'     => 0,
            'ends_planned'   => $ends,
            'hammer_start'   => $hammer,
            'status'         => $status,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Mecz zapisany.');
        $this->redirect('curling/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new CurlingMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('curling/matches');
    }

    /** Formularz do wpisywania endow + biezacy wynik. */
    public function statsForm(string $matchId): void
    {
        $mModel = new CurlingMatchModel();
        $match  = $mModel->findById((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('curling/matches');
        }
        $endModel = new CurlingMatchEndModel();
        $this->render('curling/matches/stats_form', [
            'title'    => 'Statystyki meczu — Curling',
            'match'    => $match,
            'ends'     => $endModel->listForMatch((int)$matchId),
            'totals'   => $endModel->totals((int)$matchId),
            'sportKey' => 'curling',
            'submitUrl'=> 'curling/matches/' . (int)$matchId . '/stats',
            'backUrl'  => 'curling/matches',
        ]);
    }

    public function statsSave(string $matchId): void
    {
        Csrf::verify();
        $mModel = new CurlingMatchModel();
        $match  = $mModel->findById((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('curling/matches');
        }

        $endModel    = new CurlingMatchEndModel();
        $ends        = (array)($_POST['ends'] ?? []);
        $prevHammer  = $match['hammer_start'] ?: 'away';

        // Sortuj wg end_number
        $sorted = [];
        foreach ($ends as $row) {
            if (!is_array($row)) continue;
            $n = (int)($row['end_number'] ?? 0);
            if ($n < 1 || $n > 12) continue;
            $sorted[$n] = $row;
        }
        ksort($sorted);

        foreach ($sorted as $n => $row) {
            $h = max(0, (int)($row['home_score'] ?? 0));
            $a = max(0, (int)($row['away_score'] ?? 0));
            $endModel->upsertEnd((int)$matchId, $n, $h, $a, $prevHammer);
            // Hammer dla nastepnego endu
            $prevHammer = $endModel->nextHammer($prevHammer, $h, $a);
        }

        // Aktualizacja sumy w curling_matches
        $totals = $endModel->totals((int)$matchId);
        \App\Helpers\Database::pdo()->prepare(
            "UPDATE curling_matches SET home_score=?, away_score=? WHERE id=? AND club_id=?"
        )->execute([
            $totals['home'], $totals['away'],
            (int)$matchId, \App\Helpers\ClubContext::current(),
        ]);

        Session::flash('success', 'Endy zapisane.');
        $this->redirect('curling/matches');
    }
}
