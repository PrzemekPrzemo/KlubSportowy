<?php
return [
    'key'        => 'judo',
    'name'       => 'Judo',
    'federation' => 'PZJ',
    'features'   => ['belts', 'results', 'weight_categories', 'attendance'],
    'routes' => [
        ['GET',  '/judo/belts',               [\App\Sports\Judo\Controllers\BeltsController::class,      'index']],
        ['POST', '/judo/belts/store',          [\App\Sports\Judo\Controllers\BeltsController::class,      'store']],
        ['POST', '/judo/belts/:id/delete',     [\App\Sports\Judo\Controllers\BeltsController::class,      'delete']],
        ['GET',  '/judo/results',              [\App\Sports\Judo\Controllers\ResultsController::class,    'index']],
        ['POST', '/judo/results/store',        [\App\Sports\Judo\Controllers\ResultsController::class,    'store']],
        ['POST', '/judo/results/:id/delete',   [\App\Sports\Judo\Controllers\ResultsController::class,    'delete']],
        ['GET',  '/judo/attendance',           [\App\Sports\Judo\Controllers\AttendanceController::class, 'index']],
    ],
    'nav' => [
        ['label' => 'Pasy (kyu/dan)', 'icon' => 'bi-award',         'url' => 'judo/belts'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-trophy',         'url' => 'judo/results'],
        ['label' => 'Frekwencja',     'icon' => 'bi-calendar-check', 'url' => 'judo/attendance'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
