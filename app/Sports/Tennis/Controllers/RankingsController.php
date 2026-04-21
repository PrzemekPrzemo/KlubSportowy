<?php

namespace App\Sports\Tennis\Controllers;

use App\Controllers\BaseController;
use App\Sports\Tennis\Models\TennisRankingModel;

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
        $season  = $_GET['season'] ?? (string)date('Y');
        $season  = preg_replace('/[^0-9]/', '', $season) ?: (string)date('Y');

        $model = new TennisRankingModel();
        $this->render('tennis/rankings/index', [
            'title'   => 'Ranking tenisa — sezon ' . $season,
            'ranking' => $model->ranking($season),
            'season'  => $season,
        ]);
    }
}
