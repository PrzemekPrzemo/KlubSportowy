<?php
return [
    'key'        => 'climbing',
    'name'       => 'Wspinaczka sportowa',
    'federation' => 'PZA',
    'archetype'  => \App\Sports\Climbing\ClimbingArchetype::class,
    'features'   => ['results', 'routes', 'sends', 'disciplines', 'grades', 'demo-ready'],
    'routes' => [
        ['GET',  '/climbing/results',            [\App\Sports\Climbing\Controllers\ResultsController::class, 'index']],
        ['POST', '/climbing/results/store',      [\App\Sports\Climbing\Controllers\ResultsController::class, 'store']],
        ['POST', '/climbing/results/:id/delete', [\App\Sports\Climbing\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/climbing/routes',             [\App\Sports\Climbing\Controllers\RoutesController::class,  'index']],
        ['POST', '/climbing/routes/store',       [\App\Sports\Climbing\Controllers\RoutesController::class,  'store']],
        ['POST', '/climbing/routes/:id/retire',  [\App\Sports\Climbing\Controllers\RoutesController::class,  'retire']],
        ['POST', '/climbing/routes/:id/delete',  [\App\Sports\Climbing\Controllers\RoutesController::class,  'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki wspinaczki',   'icon' => 'bi-triangle-fill', 'url' => 'climbing/results'],
        ['label' => 'Drogi klubowe',       'icon' => 'bi-list-columns',  'url' => 'climbing/routes'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
