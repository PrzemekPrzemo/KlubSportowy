<?php
return [
    'key'        => 'powerlifting',
    'name'       => 'Trójbój siłowy',
    'federation' => 'PZTSS',
    'archetype'  => \App\Sports\Powerlifting\PowerliftingArchetype::class,
    'features'   => ['results', 'records', 'weight_categories', 'demo-ready'],
    'routes' => [
        ['GET',  '/powerlifting/results',            [\App\Sports\Powerlifting\Controllers\ResultsController::class, 'index']],
        ['POST', '/powerlifting/results/store',       [\App\Sports\Powerlifting\Controllers\ResultsController::class, 'store']],
        ['POST', '/powerlifting/results/:id/delete',  [\App\Sports\Powerlifting\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/powerlifting/records',             [\App\Sports\Powerlifting\Controllers\RecordsController::class, 'index']],
        ['POST', '/powerlifting/records/store',       [\App\Sports\Powerlifting\Controllers\RecordsController::class, 'store']],
        ['POST', '/powerlifting/records/:id/delete',  [\App\Sports\Powerlifting\Controllers\RecordsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki zawodów',  'icon' => 'bi-trophy',   'url' => 'powerlifting/results'],
        ['label' => 'Rekordy klubu',   'icon' => 'bi-star',     'url' => 'powerlifting/records'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
