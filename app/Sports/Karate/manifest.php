<?php
return [
    'key'        => 'karate',
    'name'       => 'Karate',
    'federation' => 'PZKK',
    'archetype'  => \App\Sports\Karate\KarateArchetype::class,
    'features'   => ['belts', 'results', 'weight_categories', 'kata', 'kumite', 'attendance', 'calendar', 'demo-ready'],
    'routes' => [
        ['GET',  '/karate/belts',                 [\App\Sports\Karate\Controllers\BeltsController::class,      'index']],
        ['POST', '/karate/belts/store',            [\App\Sports\Karate\Controllers\BeltsController::class,      'store']],
        ['POST', '/karate/belts/:id/delete',       [\App\Sports\Karate\Controllers\BeltsController::class,      'delete']],
        ['GET',  '/karate/belts/:id/certificate',  [\App\Sports\Karate\Controllers\BeltsController::class,      'printCertificate']],
        ['GET',  '/karate/results',                [\App\Sports\Karate\Controllers\ResultsController::class,    'index']],
        ['POST', '/karate/results/store',          [\App\Sports\Karate\Controllers\ResultsController::class,    'store']],
        ['POST', '/karate/results/:id/delete',     [\App\Sports\Karate\Controllers\ResultsController::class,    'delete']],
        ['GET',  '/karate/attendance',             [\App\Sports\Karate\Controllers\AttendanceController::class, 'index']],
        ['GET',  '/karate/calendar',               [\App\Sports\Karate\Controllers\CalendarController::class,   'index']],
    ],
    'nav' => [
        ['label' => 'Pasy (kyu/dan)', 'icon' => 'bi-award',         'url' => 'karate/belts'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-trophy',         'url' => 'karate/results'],
        ['label' => 'Frekwencja',     'icon' => 'bi-calendar-check', 'url' => 'karate/attendance'],
        ['label' => 'Kalendarz',      'icon' => 'bi-calendar3',      'url' => 'karate/calendar'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
