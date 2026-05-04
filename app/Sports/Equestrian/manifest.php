<?php
return [
    'key'        => 'equestrian',
    'name'       => 'Jeździectwo',
    'federation' => 'PZJ',
    'features'   => ['horses', 'results', 'disciplines', 'dressage', 'jumping'],
    'routes' => [
        ['GET',  '/equestrian/horses',              [\App\Sports\Equestrian\Controllers\HorsesController::class,  'index']],
        ['POST', '/equestrian/horses/store',         [\App\Sports\Equestrian\Controllers\HorsesController::class,  'store']],
        ['POST', '/equestrian/horses/:id/delete',    [\App\Sports\Equestrian\Controllers\HorsesController::class,  'delete']],
        ['GET',  '/equestrian/results',              [\App\Sports\Equestrian\Controllers\ResultsController::class, 'index']],
        ['POST', '/equestrian/results/store',        [\App\Sports\Equestrian\Controllers\ResultsController::class, 'store']],
        ['POST', '/equestrian/results/:id/delete',   [\App\Sports\Equestrian\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Konie',          'icon' => 'bi-compass', 'url' => 'equestrian/horses'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-trophy',  'url' => 'equestrian/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
