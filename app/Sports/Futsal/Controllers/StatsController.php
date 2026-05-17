<?php

namespace App\Sports\Futsal\Controllers;

use App\Controllers\BaseController;
use App\Sports\Futsal\Models\FutsalMatchModel;
use App\Sports\Futsal\Models\FutsalTeamModel;

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
        $mModel = new FutsalMatchModel();
        $tModel = new FutsalTeamModel();
        $this->render('futsal/stats/dashboard', [
            'title'       => 'Dashboard — Futsal',
            'teams'       => $tModel->listForClub(),
            'topScorers'  => $mModel->topScorers(10),
            'recent'      => array_slice($mModel->listForClub(), 0, 10),
            'sportKey'    => 'futsal',
            'sportLabel'  => 'Futsal',
        ]);
    }
}
