<?php

namespace App\Sports\Chess\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Chess\Models\ChessResultModel;

class ResultsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $results = (new ChessResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('chess/results/index', [
            'title'      => 'Wyniki partii — Szachy',
            'results'    => $results,
            'members'    => $members,
            'categories' => ChessResultModel::$CATEGORIES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('chess/results');
        }

        $result   = in_array($_POST['result'] ?? '', ['win','draw','loss'], true) ? $_POST['result'] : 'draw';
        $color    = in_array($_POST['color'] ?? '', ['white','black'], true) ? $_POST['color'] : null;
        $category = array_key_exists($_POST['category'] ?? '', ChessResultModel::$CATEGORIES)
                        ? $_POST['category'] : 'classical';

        (new ChessResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'opponent_name'    => trim($_POST['opponent_name'] ?? '') ?: null,
            'result'           => $result,
            'color'            => $color,
            'opening'          => trim($_POST['opening'] ?? '') ?: null,
            'category'         => $category,
            'tournament_round' => trim($_POST['tournament_round'] ?? '') ?: null,
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'rating_change'    => isset($_POST['rating_change']) && $_POST['rating_change'] !== ''
                                    ? (int)$_POST['rating_change'] : null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('chess/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ChessResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('chess/results');
    }
}
