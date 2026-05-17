<?php

namespace App\Sports\Esport\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Esport\Models\EsportGameModel;
use App\Sports\Esport\Models\EsportMemberProfileModel;

/**
 * Panel zarzadcy klubu — esport: katalog gier, profile graczy, leaderboardy.
 *
 *   GET  /club/esport/games                 — katalog gier (global + klubowe)
 *   POST /club/esport/games/store           — dodanie wlasnej gry klubowej
 *   POST /club/esport/games/:id/deactivate  — wylaczenie gry klubowej
 *   GET  /club/esport/profiles              — lista profili graczy (z filtrem game)
 *   GET  /club/esport/leaderboard/:gameCode — leaderboard per gra
 */
class ClubEsportController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function games(): void
    {
        $games = (new EsportGameModel())->listAvailableForClub();

        $this->render('esport/club/games', [
            'title'          => 'E-sport — Katalog gier',
            'games'          => $games,
            'genres'         => EsportGameModel::GENRES,
            'rankingSystems' => EsportGameModel::RANKING_SYSTEMS,
            'formats'        => EsportGameModel::FORMATS,
        ]);
    }

    public function storeGame(): void
    {
        Csrf::verify();
        $name = trim($_POST['display_name'] ?? '');
        $code = strtolower(trim($_POST['game_code'] ?? ''));
        if ($name === '' || !preg_match('/^[a-z0-9_]{2,50}$/', $code)) {
            Session::flash('error', 'Podaj kod gry (a-z, 0-9, _) i nazwe wyswietlana.');
            $this->redirect('club/esport/games');
        }
        (new EsportGameModel())->addClubGame([
            'game_code'      => $code,
            'display_name'   => $name,
            'genre'          => $_POST['genre'] ?? 'other',
            'team_size'      => (int)($_POST['team_size'] ?? 1),
            'ranking_system' => $_POST['ranking_system'] ?? 'elo',
            'default_format' => $_POST['default_format'] ?? 'single_elim',
            'active'         => 1,
        ]);
        Session::flash('success', 'Gra dodana do katalogu klubowego.');
        $this->redirect('club/esport/games');
    }

    public function deactivateGame(string $id): void
    {
        Csrf::verify();
        (new EsportGameModel())->deactivate((int)$id);
        Session::flash('success', 'Gra dezaktywowana.');
        $this->redirect('club/esport/games');
    }

    public function profiles(): void
    {
        $gameCode = isset($_GET['game']) ? (string)$_GET['game'] : null;
        if ($gameCode !== null && !preg_match('/^[a-z0-9_]{2,50}$/', $gameCode)) {
            $gameCode = null;
        }
        $model    = new EsportMemberProfileModel();
        $profiles = $model->listForClub($gameCode);
        $games    = (new EsportGameModel())->listAvailableForClub();

        $this->render('esport/club/profiles', [
            'title'        => 'E-sport — Profile graczy',
            'profiles'     => $profiles,
            'games'        => $games,
            'currentGame'  => $gameCode,
            'platforms'    => EsportGameModel::PLATFORMS,
        ]);
    }

    public function leaderboard(string $gameCode): void
    {
        if (!preg_match('/^[a-z0-9_]{2,50}$/', $gameCode)) {
            Session::flash('error', 'Nieprawidlowy kod gry.');
            $this->redirect('club/esport/profiles');
        }
        $game = (new EsportGameModel())->findByCode($gameCode);
        if ($game === null) {
            Session::flash('error', 'Gra nie istnieje.');
            $this->redirect('club/esport/games');
        }
        $top = (new EsportMemberProfileModel())->leaderboard($gameCode, 20);
        $this->render('esport/club/leaderboard', [
            'title' => 'E-sport — Leaderboard ' . $game['display_name'],
            'game'  => $game,
            'top'   => $top,
        ]);
    }
}
