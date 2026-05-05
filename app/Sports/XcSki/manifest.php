<?php
return [
    'key'        => 'xcski',
    'name'       => 'Narciarstwo biegowe',
    'federation' => 'PZN XC',
    'archetype'  => \App\Sports\XcSki\XcSkiArchetype::class,
    'features'   => ['results', 'technique', 'distance', 'fis_points', 'demo-ready'],
    'routes' => [
        ['GET',  '/xcski/results',            [\App\Sports\XcSki\Controllers\ResultsController::class, 'index']],
        ['POST', '/xcski/results/store',      [\App\Sports\XcSki\Controllers\ResultsController::class, 'store']],
        ['POST', '/xcski/results/:id/delete', [\App\Sports\XcSki\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki biegowe', 'icon' => 'bi-stopwatch', 'url' => 'xcski/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
