<?php
return [
    'key'        => 'judo',
    'name'       => 'Judo',
    'federation' => 'PZJ',
    'archetype'  => \App\Sports\Judo\JudoArchetype::class,
    'features'   => ['belts', 'results', 'weight_categories', 'attendance', 'calendar', 'demo-ready'],
    'routes' => [
        ['GET',  '/judo/belts',                [\App\Sports\Judo\Controllers\BeltsController::class,      'index']],
        ['POST', '/judo/belts/store',           [\App\Sports\Judo\Controllers\BeltsController::class,      'store']],
        ['POST', '/judo/belts/:id/delete',      [\App\Sports\Judo\Controllers\BeltsController::class,      'delete']],
        ['GET',  '/judo/belts/:id/certificate', [\App\Sports\Judo\Controllers\BeltsController::class,      'printCertificate']],
        ['GET',  '/judo/results',               [\App\Sports\Judo\Controllers\ResultsController::class,    'index']],
        ['POST', '/judo/results/store',         [\App\Sports\Judo\Controllers\ResultsController::class,    'store']],
        ['POST', '/judo/results/:id/delete',    [\App\Sports\Judo\Controllers\ResultsController::class,    'delete']],
        ['GET',  '/judo/attendance',            [\App\Sports\Judo\Controllers\AttendanceController::class, 'index']],
        ['GET',  '/judo/calendar',              [\App\Sports\Judo\Controllers\CalendarController::class,   'index']],
    ],
    'nav' => [
        ['label' => 'Pasy (kyu/dan)', 'icon' => 'bi-award',         'url' => 'judo/belts'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-trophy',         'url' => 'judo/results'],
        ['label' => 'Frekwencja',     'icon' => 'bi-calendar-check', 'url' => 'judo/attendance'],
        ['label' => 'Kalendarz',      'icon' => 'bi-calendar3',      'url' => 'judo/calendar'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
