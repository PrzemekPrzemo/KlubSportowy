<?php
return [
    'key'        => 'swimming',
    'name'       => 'Pływanie',
    'federation' => 'PZP',
    'features'   => ['results', 'personal_bests', 'disciplines'],
    'routes' => [
        ['GET',  '/swimming/results',           [\App\Sports\Swimming\Controllers\ResultsController::class, 'index']],
        ['POST', '/swimming/results/store',      [\App\Sports\Swimming\Controllers\ResultsController::class, 'store']],
        ['POST', '/swimming/results/:id/delete', [\App\Sports\Swimming\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki pływania', 'icon' => 'bi-water', 'url' => 'swimming/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
