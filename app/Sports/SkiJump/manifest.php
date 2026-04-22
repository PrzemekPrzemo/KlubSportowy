<?php
return [
    'key'        => 'skijump',
    'name'       => 'Skoki narciarskie',
    'federation' => 'PZN SJ',
    'features'   => ['results', 'jumps', 'hill_k', 'fis_points'],
    'routes' => [
        ['GET',  '/skijump/results',            [\App\Sports\SkiJump\Controllers\ResultsController::class, 'index']],
        ['POST', '/skijump/results/store',      [\App\Sports\SkiJump\Controllers\ResultsController::class, 'store']],
        ['POST', '/skijump/results/:id/delete', [\App\Sports\SkiJump\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki skoków', 'icon' => 'bi-arrow-up-right-circle', 'url' => 'skijump/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
