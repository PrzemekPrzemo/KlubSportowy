<?php

namespace App\Sports\Bridge\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Support\Models\BridgeBoardModel;
use App\Sports\Support\Models\BridgePairModel;

/**
 * Boards tracking — IMP / MP scoring per rozdanie.
 * Tabela: sport_bridge_boards (z 106_scoring_niche_full.sql).
 */
class BoardsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('bridge');
    }

    public function index(): void
    {
        $model = new BridgeBoardModel();
        $tournamentId = !empty($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : null;
        $this->render('bridge/boards/index', [
            'title'         => 'Rozdania (boards) — Brydż',
            'boards'        => $model->listForClub($tournamentId),
            'pairs'         => (new BridgePairModel())->listForClub(),
            'tournamentId'  => $tournamentId,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $pairId = (int)($_POST['pair_id'] ?? 0);
        $boardNumber = (int)($_POST['board_number'] ?? 0);
        if ($pairId <= 0 || $boardNumber <= 0) {
            Session::flash('error', 'Wybierz parę i numer rozdania.');
            $this->redirect('bridge/boards');
        }
        $declarer = in_array($_POST['declarer'] ?? '', ['N','S','E','W'], true) ? $_POST['declarer'] : null;

        (new BridgeBoardModel())->insert([
            'tournament_id' => !empty($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : null,
            'pair_id'       => $pairId,
            'board_number'  => $boardNumber,
            'contract'      => trim((string)($_POST['contract'] ?? '')) ?: null,
            'declarer'      => $declarer,
            'result'        => isset($_POST['result']) && $_POST['result'] !== '' ? (int)$_POST['result'] : null,
            'imp_score'     => isset($_POST['imp_score']) && $_POST['imp_score'] !== '' ? (int)$_POST['imp_score'] : null,
            'mp_score'      => isset($_POST['mp_score']) && $_POST['mp_score'] !== '' ? (float)$_POST['mp_score'] : null,
        ]);
        Session::flash('success', 'Rozdanie zapisane.');
        $this->redirect('bridge/boards');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BridgeBoardModel())->delete((int)$id);
        Session::flash('success', 'Rozdanie usunięte.');
        $this->redirect('bridge/boards');
    }
}
