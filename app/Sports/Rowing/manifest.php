<?php
return [
    'key'        => 'rowing',
    'name'       => 'Wioślarstwo',
    'federation' => 'PZTW',
    'status'     => 'full',
    'module'     => \App\Sports\Rowing\RowingModule::class,
    'archetype'  => \App\Sports\Rowing\RowingArchetype::class,
    'features'   => ['results', 'boat_classes', 'timing_results', 'verified_results', 'demo-ready'],
    'routes' => [
        ['GET',  '/rowing/results',            [\App\Sports\Rowing\Controllers\ResultsController::class, 'index']],
        ['POST', '/rowing/results/store',      [\App\Sports\Rowing\Controllers\ResultsController::class, 'store']],
        ['GET',  '/rowing/results/:id',        [\App\Sports\Rowing\Controllers\ResultsController::class, 'show']],
        ['GET',  '/rowing/results/:id/edit',   [\App\Sports\Rowing\Controllers\ResultsController::class, 'edit']],
        ['POST', '/rowing/results/:id/update', [\App\Sports\Rowing\Controllers\ResultsController::class, 'update']],
        ['POST', '/rowing/results/:id/delete', [\App\Sports\Rowing\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki wioślarskie',    'icon' => 'bi-moisture',  'url' => 'rowing/results'],
        ['label' => 'Wyniki (zunifikowane)', 'icon' => 'bi-stopwatch', 'url' => 'club/sport/rowing/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
