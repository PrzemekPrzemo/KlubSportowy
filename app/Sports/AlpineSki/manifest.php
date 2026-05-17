<?php
return [
    'key'        => 'alpineski',
    'name'       => 'Narciarstwo alpejskie',
    'federation' => 'PZN',
    'status'     => 'full',
    'module'     => \App\Sports\AlpineSki\AlpineSkiModule::class,
    'archetype'  => \App\Sports\AlpineSki\AlpineSkiArchetype::class,
    'features'   => ['results', 'disciplines', 'fis_points', 'run_times', 'timing_results', 'verified_results', 'demo-ready'],
    'routes' => [
        ['GET',  '/alpineski/results',            [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'index']],
        ['POST', '/alpineski/results/store',      [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'store']],
        ['GET',  '/alpineski/results/:id',        [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'show']],
        ['GET',  '/alpineski/results/:id/edit',   [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'edit']],
        ['POST', '/alpineski/results/:id/update', [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'update']],
        ['POST', '/alpineski/results/:id/delete', [\App\Sports\AlpineSki\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki alpejskie',      'icon' => 'bi-triangle-fill', 'url' => 'alpineski/results'],
        ['label' => 'Wyniki (zunifikowane)', 'icon' => 'bi-stopwatch',     'url' => 'club/sport/alpineski/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
