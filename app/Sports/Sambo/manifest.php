<?php
return [
    'key'        => 'sambo',
    'name'       => 'Sambo',
    'federation' => 'PZSambo',
    'icon'       => 'bi-shield-shaded',
    'features'   => ['belts', 'results', 'weight_categories'],
    'routes' => [
        ['GET',  '/sambo/belts',              [\App\Sports\Sambo\Controllers\BeltsController::class,   'index']],
        ['POST', '/sambo/belts/store',         [\App\Sports\Sambo\Controllers\BeltsController::class,   'store']],
        ['POST', '/sambo/belts/:id/delete',    [\App\Sports\Sambo\Controllers\BeltsController::class,   'delete']],
        ['GET',  '/sambo/results',             [\App\Sports\Sambo\Controllers\ResultsController::class, 'index']],
        ['POST', '/sambo/results/store',       [\App\Sports\Sambo\Controllers\ResultsController::class, 'store']],
        ['POST', '/sambo/results/:id/delete',  [\App\Sports\Sambo\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pasy',             'icon' => 'bi-shield-shaded', 'url' => 'sambo/belts'],
        ['label' => 'Wyniki zawodów',   'icon' => 'bi-trophy',        'url' => 'sambo/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
