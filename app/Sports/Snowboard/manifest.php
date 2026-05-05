<?php
return [
    'key'        => 'snowboard',
    'name'       => 'Snowboard',
    'federation' => 'PZN SB',
    'archetype'  => \App\Sports\Snowboard\SnowboardArchetype::class,
    'features'   => ['results', 'disciplines', 'fis_points', 'demo-ready'],
    'routes' => [
        ['GET',  '/snowboard/results',            [\App\Sports\Snowboard\Controllers\ResultsController::class, 'index']],
        ['POST', '/snowboard/results/store',      [\App\Sports\Snowboard\Controllers\ResultsController::class, 'store']],
        ['POST', '/snowboard/results/:id/delete', [\App\Sports\Snowboard\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki snowboard', 'icon' => 'bi-snow2', 'url' => 'snowboard/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
