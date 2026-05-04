<?php

namespace App\Sports\Squash\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Squash\Models\SquashRankingModel;

class RankingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model   = new SquashRankingModel();
        $seasons = $model->seasons();
        $season  = $_GET['season'] ?? ($seasons[0] ?? '');
        $rankings = $model->listForClub($season);
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('squash/rankings/index', [
            'title'    => 'Ranking PSA — Squash',
            'rankings' => $rankings,
            'members'  => $members,
            'seasons'  => $seasons,
            'season'   => $season,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $rating   = (int)($_POST['psa_rating'] ?? 0);
        $season   = trim($_POST['season'] ?? '');

        if ($memberId <= 0 || $season === '') {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('squash/rankings');
        }

        $model = new SquashRankingModel();
        $model->updateRating($memberId, $rating, $season);
        Session::flash('success', 'Ranking zaktualizowany.');
        $this->redirect('squash/rankings');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SquashRankingModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('squash/rankings');
    }
}
