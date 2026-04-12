<?php

namespace App\Sports\Basketball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\ClubContext;
use App\Sports\Basketball\Models\BasketballPlayerStatsModel;

class StatsController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $clubId = (int)ClubContext::current();
        $statsModel = new BasketballPlayerStatsModel();
        $this->render('basketball/stats/index', [
            'title'        => 'Statystyki koszykówki',
            'topScorers'   => $statsModel->topScorers($clubId, 10),
            'topAssists'   => $statsModel->topAssists($clubId, 10),
            'topRebounders' => $statsModel->topRebounders($clubId, 10),
        ]);
    }
}
