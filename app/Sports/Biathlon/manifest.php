<?php
return [
    'key'        => 'biathlon',
    'name'       => 'Biathlon',
    'federation' => 'PZBiathlon',
    'archetype'  => \App\Sports\Biathlon\BiathlonArchetype::class,
    'features'   => ['results', 'shooting_accuracy', 'run_time', 'penalties', 'demo-ready'],
    'routes' => [
        ['GET',  '/biathlon/results',            [\App\Sports\Biathlon\Controllers\ResultsController::class, 'index']],
        ['POST', '/biathlon/results/store',      [\App\Sports\Biathlon\Controllers\ResultsController::class, 'store']],
        ['GET',  '/biathlon/results/:id',        [\App\Sports\Biathlon\Controllers\ResultsController::class, 'show']],
        ['GET',  '/biathlon/results/:id/edit',   [\App\Sports\Biathlon\Controllers\ResultsController::class, 'edit']],
        ['POST', '/biathlon/results/:id/update', [\App\Sports\Biathlon\Controllers\ResultsController::class, 'update']],
        ['POST', '/biathlon/results/:id/delete', [\App\Sports\Biathlon\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki biathlonu', 'icon' => 'bi-bullseye', 'url' => 'biathlon/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
