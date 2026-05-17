<?php

namespace App\Sports\Bridge\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\BridgePairModel;

/**
 * 2-2 bridge pairs + masterpoints ranking.
 * Tabela: sport_bridge_pairs (z 106_scoring_niche_full.sql).
 * Oddzielne od istniejacych bridge_partnerships (zachowane bez zmian).
 */
class PairsController extends BaseController
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
        $model = new BridgePairModel();
        $this->render('bridge/pairs/index', [
            'title'   => 'Pary brydżowe (N-S) — Masterpoints',
            'pairs'   => $model->listForClub(),
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $north = (int)($_POST['member_north_id'] ?? 0);
        $south = (int)($_POST['member_south_id'] ?? 0);
        if ($north <= 0 || $south <= 0 || $north === $south) {
            Session::flash('error', 'Wybierz dwóch różnych graczy (N i S).');
            $this->redirect('bridge/pairs');
        }
        (new BridgePairModel())->insert([
            'member_north_id' => $north,
            'member_south_id' => $south,
            'pair_name'       => trim((string)($_POST['pair_name'] ?? '')) ?: null,
            'masterpoints'    => isset($_POST['masterpoints']) && $_POST['masterpoints'] !== ''
                                    ? (float)$_POST['masterpoints'] : 0,
        ]);
        Session::flash('success', 'Para dodana.');
        $this->redirect('bridge/pairs');
    }

    public function addMp(string $id): void
    {
        Csrf::verify();
        $delta = (float)($_POST['masterpoints_delta'] ?? 0);
        if ($delta === 0.0) {
            Session::flash('error', 'Podaj liczbę masterpoints do dodania (>0 lub <0).');
            $this->redirect('bridge/pairs');
        }
        (new BridgePairModel())->addMasterpoints((int)$id, $delta);
        Session::flash('success', 'Masterpoints zaktualizowane.');
        $this->redirect('bridge/pairs');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BridgePairModel())->delete((int)$id);
        Session::flash('success', 'Para usunięta.');
        $this->redirect('bridge/pairs');
    }
}
