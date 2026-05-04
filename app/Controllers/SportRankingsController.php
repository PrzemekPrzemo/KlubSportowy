<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Models\MemberModel;
use App\Models\SportRankingModel;

class SportRankingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model    = new SportRankingModel();
        $sportKey = $_GET['sport'] ?? null;
        $season   = $_GET['season'] ?? date('Y');
        $rankings = $sportKey
            ? $model->listForSport($sportKey, $season)
            : [];
        $seasons  = $sportKey ? $model->seasons($sportKey) : [];
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $sports   = SportModuleLoader::all();

        $this->render('rankings/index', [
            'title'       => 'Rankingi sportowe',
            'rankings'    => $rankings,
            'seasons'     => $seasons,
            'members'     => $members,
            'sports'      => $sports,
            'filterSport' => $sportKey,
            'season'      => $season,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $sportKey = trim($_POST['sport_key'] ?? '');
        $season   = trim($_POST['season'] ?? date('Y'));
        $points   = (int)($_POST['ranking_points'] ?? 0);

        if ($memberId <= 0 || $sportKey === '') {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('sport-rankings');
        }

        (new SportRankingModel())->addPoints($memberId, $sportKey, $season, $points);
        Session::flash('success', 'Punkty dodane.');
        $this->redirect('sport-rankings?sport='.$sportKey.'&season='.$season);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SportRankingModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('sport-rankings');
    }
}
