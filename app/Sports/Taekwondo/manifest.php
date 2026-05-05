<?php
return [
    'key'        => 'taekwondo',
    'name'       => 'Taekwondo',
    'federation' => 'PZTkd',
    'archetype'  => \App\Sports\Taekwondo\TaekwondoArchetype::class,
    'features'   => ['belts', 'results', 'weight_categories', 'attendance', 'calendar', 'demo-ready'],
    'routes' => [
        ['GET',  '/taekwondo/belts',                 [\App\Sports\Taekwondo\Controllers\BeltsController::class,      'index']],
        ['POST', '/taekwondo/belts/store',            [\App\Sports\Taekwondo\Controllers\BeltsController::class,      'store']],
        ['POST', '/taekwondo/belts/:id/delete',       [\App\Sports\Taekwondo\Controllers\BeltsController::class,      'delete']],
        ['GET',  '/taekwondo/belts/:id/certificate',  [\App\Sports\Taekwondo\Controllers\BeltsController::class,      'printCertificate']],
        ['GET',  '/taekwondo/results',                [\App\Sports\Taekwondo\Controllers\ResultsController::class,    'index']],
        ['POST', '/taekwondo/results/store',          [\App\Sports\Taekwondo\Controllers\ResultsController::class,    'store']],
        ['POST', '/taekwondo/results/:id/delete',     [\App\Sports\Taekwondo\Controllers\ResultsController::class,    'delete']],
        ['GET',  '/taekwondo/attendance',             [\App\Sports\Taekwondo\Controllers\AttendanceController::class, 'index']],
        ['GET',  '/taekwondo/calendar',               [\App\Sports\Taekwondo\Controllers\CalendarController::class,   'index']],
    ],
    'nav' => [
        ['label' => 'Pasy (gup/dan)', 'icon' => 'bi-award',         'url' => 'taekwondo/belts'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-shield-fill',    'url' => 'taekwondo/results'],
        ['label' => 'Frekwencja',     'icon' => 'bi-calendar-check', 'url' => 'taekwondo/attendance'],
        ['label' => 'Kalendarz',      'icon' => 'bi-calendar3',      'url' => 'taekwondo/calendar'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
