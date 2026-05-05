<?php
return [
    'key'        => 'alpineski',
    'name'       => 'Narciarstwo alpejskie',
    'federation' => 'PZN Alpine',
    'archetype'  => \App\Sports\AlpineSki\AlpineSkiArchetype::class,
    'features'   => ['results', 'disciplines', 'fis_points', 'run_times', 'demo-ready'],
    'routes' => [
        ['GET',  '/alpineski/results',            [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'index']],
        ['POST', '/alpineski/results/store',      [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'store']],
        ['POST', '/alpineski/results/:id/delete', [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki alpejskie', 'icon' => 'bi-triangle-fill', 'url' => 'alpineski/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
