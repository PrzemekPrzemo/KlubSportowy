<?php
return [
    'key'        => 'golf',
    'name'       => 'Golf',
    'federation' => 'PZGolfa',
    'features'   => ['handicap_whs', 'rounds', 'courses', 'tees'],
    'routes' => [
        ['GET',  '/golf/handicaps',            [\App\Sports\Golf\Controllers\HandicapsController::class, 'index']],
        ['POST', '/golf/handicaps/store',      [\App\Sports\Golf\Controllers\HandicapsController::class, 'store']],
        ['POST', '/golf/handicaps/:id/delete', [\App\Sports\Golf\Controllers\HandicapsController::class, 'delete']],
        ['GET',  '/golf/rounds',               [\App\Sports\Golf\Controllers\RoundsController::class,    'index']],
        ['POST', '/golf/rounds/store',         [\App\Sports\Golf\Controllers\RoundsController::class,    'store']],
        ['POST', '/golf/rounds/:id/delete',    [\App\Sports\Golf\Controllers\RoundsController::class,    'delete']],
    ],
    'nav' => [
        ['label' => 'Handicap WHS', 'icon' => 'bi-graph-up-arrow',   'url' => 'golf/handicaps'],
        ['label' => 'Rundy',        'icon' => 'bi-circle-half',      'url' => 'golf/rounds'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
