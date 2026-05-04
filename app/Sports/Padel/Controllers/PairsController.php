<?php

namespace App\Sports\Padel\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Padel\Models\PadelPairModel;

class PairsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $cat = $_GET['category'] ?? null;
        $this->render('padel/pairs/index', [
            'title'    => 'Pary i ranking — Padel',
            'pairs'    => (new PadelPairModel())->listForClub($cat),
            'members'  => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'filterCat'=> $cat,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $p1 = (int)($_POST['player1_id'] ?? 0);
        $p2 = (int)($_POST['player2_id'] ?? 0);

        if ($p1 <= 0 || $p2 <= 0 || $p1 === $p2) {
            Session::flash('error', 'Wybierz dwóch różnych zawodników.');
            $this->redirect('padel/pairs');
        }

        $cat = in_array($_POST['category'] ?? '', ['men','women','mixed'], true) ? $_POST['category'] : 'mixed';

        (new PadelPairModel())->insert([
            'player1_id'     => $p1,
            'player2_id'     => $p2,
            'pair_name'      => trim($_POST['pair_name'] ?? '') ?: null,
            'category'       => $cat,
            'ranking_points' => (int)($_POST['ranking_points'] ?? 0),
        ]);
        Session::flash('success', 'Para dodana.');
        $this->redirect('padel/pairs');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new PadelPairModel())->delete((int)$id);
        Session::flash('success', 'Usunięto parę.');
        $this->redirect('padel/pairs');
    }
}
