<?php
return [
    'key'        => 'bridge',
    'name'       => 'Brydż sportowy',
    'federation' => 'PZBS',
    'features'   => ['partnerships', 'tournaments', 'imp_mp', 'pzbs_points'],
    'routes' => [
        ['GET',  '/bridge/partnerships',            [\App\Sports\Bridge\Controllers\PartnershipsController::class, 'index']],
        ['POST', '/bridge/partnerships/store',      [\App\Sports\Bridge\Controllers\PartnershipsController::class, 'store']],
        ['POST', '/bridge/partnerships/:id/delete', [\App\Sports\Bridge\Controllers\PartnershipsController::class, 'delete']],
        ['GET',  '/bridge/tournaments',             [\App\Sports\Bridge\Controllers\TournamentsController::class,  'index']],
        ['POST', '/bridge/tournaments/store',       [\App\Sports\Bridge\Controllers\TournamentsController::class,  'store']],
        ['POST', '/bridge/tournaments/:id/delete',  [\App\Sports\Bridge\Controllers\TournamentsController::class,  'delete']],
    ],
    'nav' => [
        ['label' => 'Pary brydżowe',  'icon' => 'bi-people',    'url' => 'bridge/partnerships'],
        ['label' => 'Turnieje brydża','icon' => 'bi-trophy',     'url' => 'bridge/tournaments'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
