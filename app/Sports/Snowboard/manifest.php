<?php
return [
    'key'        => 'snowboard',
    'name'       => 'Snowboard',
    'federation' => 'PZN',
    'status'     => 'full',
    'module'     => \App\Sports\Snowboard\SnowboardModule::class,
    'archetype'  => \App\Sports\Snowboard\SnowboardArchetype::class,
    'features'   => ['results', 'disciplines', 'fis_points', 'timing_results', 'verified_results', 'demo-ready'],
    'routes' => [
        ['GET',  '/snowboard/results',            [\App\Sports\Snowboard\Controllers\ResultsController::class, 'index']],
        ['POST', '/snowboard/results/store',      [\App\Sports\Snowboard\Controllers\ResultsController::class, 'store']],
        ['GET',  '/snowboard/results/:id',        [\App\Sports\Snowboard\Controllers\ResultsController::class, 'show']],
        ['GET',  '/snowboard/results/:id/edit',   [\App\Sports\Snowboard\Controllers\ResultsController::class, 'edit']],
        ['POST', '/snowboard/results/:id/update', [\App\Sports\Snowboard\Controllers\ResultsController::class, 'update']],
        ['POST', '/snowboard/results/:id/delete', [\App\Sports\Snowboard\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki snowboard',      'icon' => 'bi-snow2',     'url' => 'snowboard/results'],
        ['label' => 'Wyniki (zunifikowane)', 'icon' => 'bi-stopwatch', 'url' => 'club/sport/snowboard/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
