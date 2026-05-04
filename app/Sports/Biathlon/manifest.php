<?php
return [
    'key'        => 'biathlon',
    'name'       => 'Biathlon',
    'federation' => 'PZBiathlon',
    'features'   => ['results', 'shooting_accuracy', 'run_time', 'penalties'],
    'routes' => [
        ['GET',  '/biathlon/results',            [\App\Sports\Biathlon\Controllers\ResultsController::class, 'index']],
        ['POST', '/biathlon/results/store',      [\App\Sports\Biathlon\Controllers\ResultsController::class, 'store']],
        ['POST', '/biathlon/results/:id/delete', [\App\Sports\Biathlon\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki biathlonu', 'icon' => 'bi-bullseye', 'url' => 'biathlon/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
