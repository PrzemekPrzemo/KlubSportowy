<?php
return [
    'key'        => 'biathlon',
    'name'       => 'Biathlon',
    'federation' => 'PZBiath',
    'status'     => 'full',
    'module'     => \App\Sports\Biathlon\BiathlonModule::class,
    'archetype'  => \App\Sports\Biathlon\BiathlonArchetype::class,
    'features'   => ['results', 'shooting_accuracy', 'run_time', 'penalties', 'timing_results', 'verified_results', 'demo-ready'],
    'routes' => [
        ['GET',  '/biathlon/results',            [\App\Sports\Biathlon\Controllers\ResultsController::class, 'index']],
        ['POST', '/biathlon/results/store',      [\App\Sports\Biathlon\Controllers\ResultsController::class, 'store']],
        ['GET',  '/biathlon/results/:id',        [\App\Sports\Biathlon\Controllers\ResultsController::class, 'show']],
        ['GET',  '/biathlon/results/:id/edit',   [\App\Sports\Biathlon\Controllers\ResultsController::class, 'edit']],
        ['POST', '/biathlon/results/:id/update', [\App\Sports\Biathlon\Controllers\ResultsController::class, 'update']],
        ['POST', '/biathlon/results/:id/delete', [\App\Sports\Biathlon\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki biathlonu',      'icon' => 'bi-bullseye',  'url' => 'biathlon/results'],
        ['label' => 'Wyniki (zunifikowane)', 'icon' => 'bi-stopwatch', 'url' => 'club/sport/biathlon/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
