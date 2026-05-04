<?php
return [
    'key'        => 'kayaking',
    'name'       => 'Kajakarstwo',
    'federation' => 'PZKajak',
    'features'   => ['boats', 'results', 'disciplines', 'distance_time'],
    'routes' => [
        ['GET',  '/kayaking/boats',              [\App\Sports\Kayaking\Controllers\BoatsController::class,   'index']],
        ['POST', '/kayaking/boats/store',        [\App\Sports\Kayaking\Controllers\BoatsController::class,   'store']],
        ['POST', '/kayaking/boats/:id/delete',   [\App\Sports\Kayaking\Controllers\BoatsController::class,   'delete']],
        ['GET',  '/kayaking/results',            [\App\Sports\Kayaking\Controllers\ResultsController::class, 'index']],
        ['POST', '/kayaking/results/store',      [\App\Sports\Kayaking\Controllers\ResultsController::class, 'store']],
        ['POST', '/kayaking/results/:id/delete', [\App\Sports\Kayaking\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Łodzie kajakowe', 'icon' => 'bi-water',          'url' => 'kayaking/boats'],
        ['label' => 'Wyniki',          'icon' => 'bi-stopwatch-fill', 'url' => 'kayaking/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
