<?php
return [
    'key'        => 'karate',
    'name'       => 'Karate',
    'federation' => 'PZKK',
    'features'   => ['belts', 'results', 'weight_categories', 'kata', 'kumite', 'attendance'],
    'routes' => [
        ['GET',  '/karate/belts',                [\App\Sports\Karate\Controllers\BeltsController::class,      'index']],
        ['POST', '/karate/belts/store',           [\App\Sports\Karate\Controllers\BeltsController::class,      'store']],
        ['POST', '/karate/belts/:id/delete',      [\App\Sports\Karate\Controllers\BeltsController::class,      'delete']],
        ['GET',  '/karate/results',               [\App\Sports\Karate\Controllers\ResultsController::class,    'index']],
        ['POST', '/karate/results/store',         [\App\Sports\Karate\Controllers\ResultsController::class,    'store']],
        ['POST', '/karate/results/:id/delete',    [\App\Sports\Karate\Controllers\ResultsController::class,    'delete']],
        ['GET',  '/karate/attendance',            [\App\Sports\Karate\Controllers\AttendanceController::class, 'index']],
    ],
    'nav' => [
        ['label' => 'Pasy (kyu/dan)', 'icon' => 'bi-award',         'url' => 'karate/belts'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-trophy',         'url' => 'karate/results'],
        ['label' => 'Frekwencja',     'icon' => 'bi-calendar-check', 'url' => 'karate/attendance'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
