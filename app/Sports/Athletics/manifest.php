<?php
return [
    'key'        => 'athletics',
    'name'       => 'Lekka atletyka',
    'federation' => 'PZLA',
    'features'   => ['disciplines','records','times','pzla_license','results'],
    'routes' => [
        ['GET',  '/athletics/records',             [\App\Sports\Athletics\Controllers\RecordsController::class, 'index']],
        ['GET',  '/athletics/records/create',      [\App\Sports\Athletics\Controllers\RecordsController::class, 'create']],
        ['POST', '/athletics/records/store',       [\App\Sports\Athletics\Controllers\RecordsController::class, 'store']],
        ['POST', '/athletics/records/:id/delete',  [\App\Sports\Athletics\Controllers\RecordsController::class, 'delete']],
        ['GET',  '/athletics/results',             [\App\Sports\Athletics\Controllers\ResultsController::class, 'index']],
        ['POST', '/athletics/results/store',       [\App\Sports\Athletics\Controllers\ResultsController::class, 'store']],
        ['POST', '/athletics/results/:id/delete',  [\App\Sports\Athletics\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Rekordy',          'icon' => 'bi-trophy',    'url' => 'athletics/records'],
        ['label' => 'Wyniki zawodów',   'icon' => 'bi-flag',      'url' => 'athletics/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
