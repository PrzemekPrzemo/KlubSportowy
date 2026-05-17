<?php

namespace App\Sports\WaterPolo\Controllers;

use App\Controllers\BaseController;
use App\Sports\WaterPolo\Models\WaterPoloMatchModel;
use App\Sports\WaterPolo\Models\WaterPoloTeamModel;

class StatsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function dashboard(): void
    {
        $mModel = new WaterPoloMatchModel();
        $tModel = new WaterPoloTeamModel();
        $this->render('water_polo/stats/dashboard', [
            'title'       => 'Dashboard — Piłka wodna',
            'teams'       => $tModel->listForClub(),
            'topScorers'  => $mModel->topScorers(10),
            'recent'      => array_slice($mModel->listForClub(), 0, 10),
            'sportKey'    => 'water_polo',
            'sportLabel'  => 'Piłka wodna',
        ]);
    }
}
