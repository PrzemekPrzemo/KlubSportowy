<?php

namespace App\Sports\Curling\Controllers;

use App\Controllers\BaseController;
use App\Sports\Curling\Models\CurlingMatchModel;
use App\Sports\Curling\Models\CurlingTeamModel;

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
        $mModel = new CurlingMatchModel();
        $tModel = new CurlingTeamModel();
        $this->render('curling/stats/dashboard', [
            'title'      => 'Dashboard — Curling',
            'teams'      => $tModel->listForClub(),
            'recent'     => array_slice($mModel->listForClub(), 0, 10),
            'sportKey'   => 'curling',
            'sportLabel' => 'Curling',
        ]);
    }
}
