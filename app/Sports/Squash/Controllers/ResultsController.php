<?php

namespace App\Sports\Squash\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Squash\Models\SquashResultModel;

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
        $results = (new SquashResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('squash/results/index', [
            'title'      => 'Wyniki meczy — Squash',
            'results'    => $results,
            'members'    => $members,
            'categories' => SquashResultModel::$CATEGORIES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('squash/results');
        }

        $category = array_key_exists($_POST['category'] ?? '', SquashResultModel::$CATEGORIES)
                        ? $_POST['category'] : 'singles';

        (new SquashResultModel())->insert([
            'member_id'           => $memberId,
            'competition_name'    => trim($_POST['competition_name'] ?? '') ?: null,
            'match_date'          => trim($_POST['match_date'] ?? '') ?: date('Y-m-d'),
            'opponent_name'       => trim($_POST['opponent_name'] ?? '') ?: null,
            'category'            => $category,
            'sets_won'            => !empty($_POST['sets_won'])  ? (int)$_POST['sets_won']  : null,
            'sets_lost'           => !empty($_POST['sets_lost']) ? (int)$_POST['sets_lost'] : null,
            'games_detail'        => trim($_POST['games_detail'] ?? '') ?: null,
            'psa_ranking_before'  => !empty($_POST['psa_ranking_before']) ? (int)$_POST['psa_ranking_before'] : null,
            'psa_ranking_after'   => !empty($_POST['psa_ranking_after'])  ? (int)$_POST['psa_ranking_after']  : null,
            'competition_round'   => trim($_POST['competition_round'] ?? '') ?: null,
            'placement'           => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'age_category'        => trim($_POST['age_category'] ?? '') ?: null,
            'notes'               => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('squash/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SquashResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('squash/results');
    }
}
