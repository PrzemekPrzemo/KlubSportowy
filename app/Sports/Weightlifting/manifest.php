<?php
return [
    'key'        => 'weightlifting',
    'name'       => 'Podnoszenie ciężarów',
    'federation' => 'PKC',
    'archetype'  => \App\Sports\Weightlifting\WeightliftingArchetype::class,
    'features'   => ['results', 'weight_categories', 'sinclair', 'club_records', 'personal_bests', 'demo-ready'],
    'routes' => [
        ['GET',  '/weightlifting/results',            [\App\Sports\Weightlifting\Controllers\ResultsController::class, 'index']],
        ['POST', '/weightlifting/results/store',      [\App\Sports\Weightlifting\Controllers\ResultsController::class, 'store']],
        ['POST', '/weightlifting/results/:id/delete', [\App\Sports\Weightlifting\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/weightlifting/records',            [\App\Sports\Weightlifting\Controllers\RecordsController::class, 'index']],
        ['POST', '/weightlifting/records/store',      [\App\Sports\Weightlifting\Controllers\RecordsController::class, 'store']],
        ['POST', '/weightlifting/records/:id/delete', [\App\Sports\Weightlifting\Controllers\RecordsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-bar-chart-steps', 'url' => 'weightlifting/results'],
        ['label' => 'Rekordy',        'icon' => 'bi-trophy-fill',     'url' => 'weightlifting/records'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
