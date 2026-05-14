<?php
return [
    'key'        => 'skijump',
    'name'       => 'Skoki narciarskie',
    'federation' => 'PZN SJ',
    'archetype'  => \App\Sports\SkiJump\SkiJumpArchetype::class,
    'features'   => ['results', 'jumps', 'hill_k', 'fis_points', 'demo-ready'],
    'routes' => [
        ['GET',  '/skijump/results',            [\App\Sports\SkiJump\Controllers\ResultsController::class, 'index']],
        ['POST', '/skijump/results/store',      [\App\Sports\SkiJump\Controllers\ResultsController::class, 'store']],
        ['GET',  '/skijump/results/:id',        [\App\Sports\SkiJump\Controllers\ResultsController::class, 'show']],
        ['GET',  '/skijump/results/:id/edit',   [\App\Sports\SkiJump\Controllers\ResultsController::class, 'edit']],
        ['POST', '/skijump/results/:id/update', [\App\Sports\SkiJump\Controllers\ResultsController::class, 'update']],
        ['POST', '/skijump/results/:id/delete', [\App\Sports\SkiJump\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki skoków', 'icon' => 'bi-arrow-up-right-circle', 'url' => 'skijump/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
