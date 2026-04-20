<?php
return [
    'key'        => 'taekwondo',
    'name'       => 'Taekwondo',
    'federation' => 'PZTkd',
    'features'   => ['belts', 'results', 'weight_categories'],
    'routes' => [
        ['GET',  '/taekwondo/belts',               [\App\Sports\Taekwondo\Controllers\BeltsController::class,   'index']],
        ['POST', '/taekwondo/belts/store',          [\App\Sports\Taekwondo\Controllers\BeltsController::class,   'store']],
        ['POST', '/taekwondo/belts/:id/delete',     [\App\Sports\Taekwondo\Controllers\BeltsController::class,   'delete']],
        ['GET',  '/taekwondo/results',              [\App\Sports\Taekwondo\Controllers\ResultsController::class, 'index']],
        ['POST', '/taekwondo/results/store',        [\App\Sports\Taekwondo\Controllers\ResultsController::class, 'store']],
        ['POST', '/taekwondo/results/:id/delete',   [\App\Sports\Taekwondo\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pasy (gup/dan)', 'icon' => 'bi-award',       'url' => 'taekwondo/belts'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-shield-fill',  'url' => 'taekwondo/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
