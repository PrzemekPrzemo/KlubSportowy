<?php
return [
    'key'        => 'boxing',
    'name'       => 'Boks',
    'federation' => 'PZBoks',
    'features'   => ['results'],
    'routes' => [
        ['GET',  '/boxing/results',              [\App\Sports\Boxing\Controllers\ResultsController::class, 'index']],
        ['POST', '/boxing/results/store',         [\App\Sports\Boxing\Controllers\ResultsController::class, 'store']],
        ['POST', '/boxing/results/:id/delete',    [\App\Sports\Boxing\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-hand-thumbs-up', 'url' => 'boxing/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
