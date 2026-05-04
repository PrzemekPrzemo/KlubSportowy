<?php
return [
    'key'        => 'swimming',
    'name'       => 'Pływanie',
    'federation' => 'PZP',
    'features'   => ['results', 'personal_bests', 'disciplines', 'club_records'],
    'routes' => [
        ['GET',  '/swimming/results',           [\App\Sports\Swimming\Controllers\ResultsController::class, 'index']],
        ['POST', '/swimming/results/store',      [\App\Sports\Swimming\Controllers\ResultsController::class, 'store']],
        ['POST', '/swimming/results/:id/delete', [\App\Sports\Swimming\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/swimming/records',            [\App\Sports\Swimming\Controllers\RecordsController::class, 'index']],
    ],
    'nav' => [
        ['label' => 'Wyniki pływania', 'icon' => 'bi-water',  'url' => 'swimming/results'],
        ['label' => 'Rekordy klubu',   'icon' => 'bi-trophy', 'url' => 'swimming/records'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
