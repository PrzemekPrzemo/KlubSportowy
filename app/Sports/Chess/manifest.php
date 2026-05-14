<?php
return [
    'key'        => 'chess',
    'name'       => 'Szachy',
    'federation' => 'PZSzach',
    'icon'       => 'bi-grid-3x3',
    'archetype'  => \App\Sports\Chess\ChessArchetype::class,
    'features'   => ['ratings', 'results', 'demo-ready'],
    'routes' => [
        ['GET',  '/chess/ratings',              [\App\Sports\Chess\Controllers\RatingsController::class, 'index']],
        ['POST', '/chess/ratings/store',         [\App\Sports\Chess\Controllers\RatingsController::class, 'store']],
        ['POST', '/chess/ratings/:id/delete',    [\App\Sports\Chess\Controllers\RatingsController::class, 'delete']],
        ['GET',  '/chess/results',              [\App\Sports\Chess\Controllers\ResultsController::class, 'index']],
        ['POST', '/chess/results/store',         [\App\Sports\Chess\Controllers\ResultsController::class, 'store']],
        ['POST', '/chess/results/:id/delete',    [\App\Sports\Chess\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Rankingi ELO',   'icon' => 'bi-bar-chart',  'url' => 'chess/ratings'],
        ['label' => 'Wyniki partii',  'icon' => 'bi-grid-3x3',   'url' => 'chess/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
