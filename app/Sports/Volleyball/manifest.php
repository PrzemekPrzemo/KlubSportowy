<?php
// ============================================================
// Moduł sportu: SIATKÓWKA (PZPS)
// ============================================================

return [
    'key'        => 'volleyball',
    'name'       => 'Siatkówka',
    'federation' => 'PZPS',
    'archetype'  => \App\Sports\Volleyball\VolleyballArchetype::class,
    'features'   => [
        'teams',
        'positions',      // atakujący, przyjmujący, środkowy, rozgrywający, libero
        'matches',
        'sets',           // statystyki per set
        'player_stats',   // ataki, bloki, serwisy, asy
        'transfers',
        'pzps_license',
        'demo-ready',
    ],
    'routes' => [
        ['GET',  '/volleyball/teams',              [\App\Sports\Volleyball\Controllers\TeamsController::class, 'index']],
        ['GET',  '/volleyball/teams/create',       [\App\Sports\Volleyball\Controllers\TeamsController::class, 'create']],
        ['POST', '/volleyball/teams/store',        [\App\Sports\Volleyball\Controllers\TeamsController::class, 'store']],
        ['GET',  '/volleyball/teams/:id/edit',     [\App\Sports\Volleyball\Controllers\TeamsController::class, 'edit']],
        ['POST', '/volleyball/teams/:id/update',   [\App\Sports\Volleyball\Controllers\TeamsController::class, 'update']],
        ['POST', '/volleyball/teams/:id/delete',   [\App\Sports\Volleyball\Controllers\TeamsController::class, 'delete']],
        ['GET',  '/volleyball/matches',            [\App\Sports\Volleyball\Controllers\MatchesController::class, 'index']],
        ['GET',  '/volleyball/matches/create',     [\App\Sports\Volleyball\Controllers\MatchesController::class, 'create']],
        ['POST', '/volleyball/matches/store',      [\App\Sports\Volleyball\Controllers\MatchesController::class, 'store']],
        ['GET',  '/volleyball/matches/:id',        [\App\Sports\Volleyball\Controllers\MatchesController::class, 'show']],
        ['GET',  '/volleyball/matches/:id/edit',   [\App\Sports\Volleyball\Controllers\MatchesController::class, 'edit']],
        ['POST', '/volleyball/matches/:id/update', [\App\Sports\Volleyball\Controllers\MatchesController::class, 'update']],
        ['POST', '/volleyball/matches/:id/delete', [\App\Sports\Volleyball\Controllers\MatchesController::class, 'delete']],
        ['POST', '/volleyball/matches/:id/stats',  [\App\Sports\Volleyball\Controllers\MatchesController::class, 'addStats']],
        ['GET',  '/volleyball/stats',              [\App\Sports\Volleyball\Controllers\StatsController::class, 'index']],
        ['GET',  '/volleyball/transfers',           [\App\Sports\Volleyball\Controllers\TransfersController::class, 'index']],
        ['GET',  '/volleyball/transfers/create',    [\App\Sports\Volleyball\Controllers\TransfersController::class, 'create']],
        ['POST', '/volleyball/transfers/store',     [\App\Sports\Volleyball\Controllers\TransfersController::class, 'store']],
        ['POST', '/volleyball/transfers/:id/delete',[\App\Sports\Volleyball\Controllers\TransfersController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-people',           'url' => 'volleyball/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-circle',           'url' => 'volleyball/matches'],
        ['label' => 'Transfery',  'icon' => 'bi-arrow-left-right', 'url' => 'volleyball/transfers'],
        ['label' => 'Statystyki', 'icon' => 'bi-bar-chart',        'url' => 'volleyball/stats'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
