<?php

namespace App\Sports\Tennis\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Tennis\Models\TennisMatchModel;
use App\Sports\Tennis\Models\TennisRankingModel;

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
        $surfaceFilter = $_GET['surface'] ?? null;
        if ($surfaceFilter && !array_key_exists($surfaceFilter, TennisMatchModel::$SURFACES)) {
            $surfaceFilter = null;
        }

        $model   = new TennisMatchModel();
        $matches = $model->listForClub(null, $surfaceFilter);
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('tennis/matches/index', [
            'title'       => 'Mecze tenisa',
            'matches'     => $matches,
            'members'     => $members,
            'surfaces'    => TennisMatchModel::$SURFACES,
            'matchTypes'  => TennisMatchModel::$MATCH_TYPES,
            'surfaceFilter' => $surfaceFilter,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $p1 = (int)($_POST['player1_id'] ?? 0);
        $p2 = (int)($_POST['player2_id'] ?? 0);
        if ($p1 <= 0 || $p2 <= 0 || $p1 === $p2) {
            Session::flash('error', 'Wybierz dwóch różnych zawodników.');
            $this->redirect('tennis/matches');
        }

        $sets    = trim($_POST['sets'] ?? '');
        $surface = $_POST['surface'] ?? 'hard';
        $type    = $_POST['match_type'] ?? 'towarzyski';
        if (!array_key_exists($surface, TennisMatchModel::$SURFACES)) $surface = 'hard';
        if (!array_key_exists($type, TennisMatchModel::$MATCH_TYPES)) $type = 'towarzyski';

        $winnerId = (int)($_POST['winner_id'] ?? 0);
        if ($winnerId !== $p1 && $winnerId !== $p2) $winnerId = 0;

        $matchModel = new TennisMatchModel();
        $matchModel->insert([
            'player1_id'   => $p1,
            'player2_id'   => $p2,
            'match_date'   => trim($_POST['match_date'] ?? '') ?: date('Y-m-d'),
            'surface'      => $surface,
            'match_type'   => $type,
            'sets'         => $sets,
            'winner_id'    => $winnerId ?: null,
            'tournament'   => trim($_POST['tournament'] ?? '') ?: null,
            'duration_min' => !empty($_POST['duration_min']) ? (int)$_POST['duration_min'] : null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);

        // Aktualizuj ranking jeśli znany zwycięzca
        if ($winnerId > 0 && $type !== 'treningowy') {
            $loser = ($winnerId === $p1) ? $p2 : $p1;
            (new TennisRankingModel())->bumpAfterMatch($winnerId, $loser, $type);
        }

        Session::flash('success', 'Mecz dodany' . ($winnerId ? ' — ranking zaktualizowany.' : '.'));
        $this->redirect('tennis/matches');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new TennisMatchModel())->delete((int)$id);
        Session::flash('success', 'Mecz usunięty.');
        $this->redirect('tennis/matches');
    }
}
