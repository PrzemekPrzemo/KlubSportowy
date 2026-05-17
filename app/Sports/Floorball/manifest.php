<?php
return [
    'key'        => 'floorball',
    'name'       => 'Floorball (Unihokej)',
    'federation' => 'PFUF',
    'archetype'  => \App\Sports\Floorball\FloorballArchetype::class,
    'team_sport' => true,
    'features'   => ['teams', 'players', 'matches', 'stats', 'penalties', 'demo-ready'],
    'routes' => [
        ['GET',  '/floorball/teams',                    [\App\Sports\Floorball\Controllers\TeamsController::class,   'index']],
        ['POST', '/floorball/teams/store',              [\App\Sports\Floorball\Controllers\TeamsController::class,   'store']],
        ['POST', '/floorball/teams/:id/delete',         [\App\Sports\Floorball\Controllers\TeamsController::class,   'delete']],
        ['POST', '/floorball/teams/:id/player/add',     [\App\Sports\Floorball\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/floorball/teams/:id/player/remove',  [\App\Sports\Floorball\Controllers\TeamsController::class,   'removePlayer']],
        ['GET',  '/floorball/matches',                  [\App\Sports\Floorball\Controllers\MatchesController::class, 'index']],
        ['POST', '/floorball/matches/store',            [\App\Sports\Floorball\Controllers\MatchesController::class, 'store']],
        ['POST', '/floorball/matches/:id/result',       [\App\Sports\Floorball\Controllers\MatchesController::class, 'saveResult']],
        ['POST', '/floorball/matches/:id/delete',       [\App\Sports\Floorball\Controllers\MatchesController::class, 'delete']],
        // Stats (FULL)
        ['GET',  '/floorball/matches/:id/stats',        [\App\Sports\Floorball\Controllers\StatsController::class,   'statsForm']],
        ['POST', '/floorball/matches/:id/stats',        [\App\Sports\Floorball\Controllers\StatsController::class,   'statsSave']],
        ['GET',  '/floorball/dashboard',                [\App\Sports\Floorball\Controllers\StatsController::class,   'dashboard']],
    ],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-people-fill',     'url' => 'floorball/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-calendar2-check', 'url' => 'floorball/matches'],
        ['label' => 'Statystyki', 'icon' => 'bi-bar-chart',       'url' => 'floorball/dashboard'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
