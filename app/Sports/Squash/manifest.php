<?php
return [
    'key'        => 'squash',
    'name'       => 'Squash',
    'federation' => 'PSqA',
    'icon'       => 'bi-circle',
    'archetype'  => \App\Sports\Squash\SquashArchetype::class,
    'features'   => ['results', 'rankings', 'demo-ready'],
    'routes' => [
        ['GET',  '/squash/results',               [\App\Sports\Squash\Controllers\ResultsController::class,  'index']],
        ['POST', '/squash/results/store',          [\App\Sports\Squash\Controllers\ResultsController::class,  'store']],
        ['POST', '/squash/results/:id/delete',     [\App\Sports\Squash\Controllers\ResultsController::class,  'delete']],
        ['GET',  '/squash/rankings',               [\App\Sports\Squash\Controllers\RankingsController::class, 'index']],
        ['POST', '/squash/rankings/store',         [\App\Sports\Squash\Controllers\RankingsController::class, 'store']],
        ['POST', '/squash/rankings/:id/delete',    [\App\Sports\Squash\Controllers\RankingsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki meczy',  'icon' => 'bi-circle',     'url' => 'squash/results'],
        ['label' => 'Ranking PSA',   'icon' => 'bi-bar-chart',  'url' => 'squash/rankings'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
