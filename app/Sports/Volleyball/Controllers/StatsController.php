<?php

namespace App\Sports\Volleyball\Controllers;

use App\Controllers\BaseController;
use App\Helpers\ClubContext;
use App\Sports\Volleyball\Models\VolleyballPlayerStatsModel;

class StatsController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $clubId = ClubContext::current();
        $statsModel = new VolleyballPlayerStatsModel();

        $topKillers  = $statsModel->topByColumn($clubId, 'kills', 10);
        $topBlockers = $statsModel->topByColumn($clubId, 'blocks', 10);
        $topServers  = $statsModel->topByColumn($clubId, 'aces', 10);

        $this->render('volleyball/stats/index', [
            'title'       => 'Statystyki siatkówki',
            'topKillers'  => $topKillers,
            'topBlockers' => $topBlockers,
            'topServers'  => $topServers,
        ]);
    }
}
