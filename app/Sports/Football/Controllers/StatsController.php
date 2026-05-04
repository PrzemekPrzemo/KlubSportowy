<?php

namespace App\Sports\Football\Controllers;

use App\Controllers\BaseController;
use App\Helpers\ClubContext;
use App\Sports\Football\Models\FootballStatsModel;

class StatsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $clubId = (int)ClubContext::current();
        $stats  = new FootballStatsModel();

        $this->render('football/stats/index', [
            'title'        => 'Statystyki piłki nożnej',
            'summary'      => $stats->summary($clubId),
            'topScorers'   => $stats->topScorers($clubId, 10),
            'topAssists'   => $stats->topAssists($clubId, 10),
            'topYellow'    => $stats->topYellowCards($clubId, 10),
            'topRed'       => $stats->topRedCards($clubId, 10),
        ]);
    }
}
