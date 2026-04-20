<?php
return [
    'key'        => 'dance_sport',
    'name'       => 'Taniec sportowy',
    'federation' => 'PZTS',
    'features'   => ['couples', 'results', 'classes', 'standard', 'latin'],
    'routes' => [
        ['GET',  '/dance_sport/couples',              [\App\Sports\DanceSport\Controllers\CouplesController::class, 'index']],
        ['POST', '/dance_sport/couples/store',         [\App\Sports\DanceSport\Controllers\CouplesController::class, 'store']],
        ['POST', '/dance_sport/couples/:id/delete',    [\App\Sports\DanceSport\Controllers\CouplesController::class, 'delete']],
        ['GET',  '/dance_sport/results',               [\App\Sports\DanceSport\Controllers\ResultsController::class, 'index']],
        ['POST', '/dance_sport/results/store',         [\App\Sports\DanceSport\Controllers\ResultsController::class, 'store']],
        ['POST', '/dance_sport/results/:id/delete',    [\App\Sports\DanceSport\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pary taneczne',  'icon' => 'bi-music-note-beamed', 'url' => 'dance_sport/couples'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-trophy',            'url' => 'dance_sport/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
