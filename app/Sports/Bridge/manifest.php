<?php
return [
    'key'        => 'bridge',
    'name'       => 'Brydż sportowy',
    'federation' => 'PZBS',
    'archetype'  => \App\Sports\Bridge\BridgeArchetype::class,
    'features'   => ['partnerships', 'tournaments', 'imp_mp', 'pzbs_points', 'pairs_ranking', 'masterpoints', 'boards', 'demo-ready'],
    'routes' => [
        ['GET',  '/bridge/partnerships',            [\App\Sports\Bridge\Controllers\PartnershipsController::class, 'index']],
        ['POST', '/bridge/partnerships/store',      [\App\Sports\Bridge\Controllers\PartnershipsController::class, 'store']],
        ['POST', '/bridge/partnerships/:id/delete', [\App\Sports\Bridge\Controllers\PartnershipsController::class, 'delete']],
        ['GET',  '/bridge/tournaments',             [\App\Sports\Bridge\Controllers\TournamentsController::class,  'index']],
        ['POST', '/bridge/tournaments/store',       [\App\Sports\Bridge\Controllers\TournamentsController::class,  'store']],
        ['POST', '/bridge/tournaments/:id/delete',  [\App\Sports\Bridge\Controllers\TournamentsController::class,  'delete']],
        // FULL: pary N-S z masterpoints + rozdania (boards)
        ['GET',  '/bridge/pairs',                   [\App\Sports\Bridge\Controllers\PairsController::class, 'index']],
        ['POST', '/bridge/pairs/store',             [\App\Sports\Bridge\Controllers\PairsController::class, 'store']],
        ['POST', '/bridge/pairs/:id/mp',            [\App\Sports\Bridge\Controllers\PairsController::class, 'addMp']],
        ['POST', '/bridge/pairs/:id/delete',        [\App\Sports\Bridge\Controllers\PairsController::class, 'delete']],
        ['GET',  '/bridge/boards',                  [\App\Sports\Bridge\Controllers\BoardsController::class, 'index']],
        ['POST', '/bridge/boards/store',            [\App\Sports\Bridge\Controllers\BoardsController::class, 'store']],
        ['POST', '/bridge/boards/:id/delete',       [\App\Sports\Bridge\Controllers\BoardsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pary brydżowe',     'icon' => 'bi-people',         'url' => 'bridge/partnerships'],
        ['label' => 'Turnieje brydża',   'icon' => 'bi-trophy',         'url' => 'bridge/tournaments'],
        ['label' => 'Pary N-S + MP',     'icon' => 'bi-bar-chart-line', 'url' => 'bridge/pairs'],
        ['label' => 'Rozdania (boards)', 'icon' => 'bi-grid-3x3-gap',   'url' => 'bridge/boards'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
