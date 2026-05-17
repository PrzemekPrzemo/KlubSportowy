<?php
return [
    'key'        => 'climbing',
    'name'       => 'Wspinaczka sportowa',
    'federation' => 'PZA',
    'archetype'  => \App\Sports\Climbing\ClimbingArchetype::class,
    'features'   => ['results', 'routes', 'sends', 'disciplines', 'grades', 'ifsc_library', 'attempts_log', 'grade_progression', 'demo-ready'],
    'routes' => [
        ['GET',  '/climbing/results',            [\App\Sports\Climbing\Controllers\ResultsController::class, 'index']],
        ['POST', '/climbing/results/store',      [\App\Sports\Climbing\Controllers\ResultsController::class, 'store']],
        ['POST', '/climbing/results/:id/delete', [\App\Sports\Climbing\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/climbing/routes',             [\App\Sports\Climbing\Controllers\RoutesController::class,  'index']],
        ['POST', '/climbing/routes/store',       [\App\Sports\Climbing\Controllers\RoutesController::class,  'store']],
        ['POST', '/climbing/routes/:id/retire',  [\App\Sports\Climbing\Controllers\RoutesController::class,  'retire']],
        ['POST', '/climbing/routes/:id/delete',  [\App\Sports\Climbing\Controllers\RoutesController::class,  'delete']],
        // FULL: IFSC route library (lead/bouldering/speed) + attempts log
        ['GET',  '/climbing/library',            [\App\Sports\Climbing\Controllers\LibraryController::class, 'index']],
        ['POST', '/climbing/library/store',      [\App\Sports\Climbing\Controllers\LibraryController::class, 'store']],
        ['POST', '/climbing/library/:id/retire', [\App\Sports\Climbing\Controllers\LibraryController::class, 'retire']],
        ['POST', '/climbing/library/:id/delete', [\App\Sports\Climbing\Controllers\LibraryController::class, 'delete']],
        ['GET',  '/climbing/attempts',           [\App\Sports\Climbing\Controllers\AttemptsController::class, 'index']],
        ['POST', '/climbing/attempts/store',     [\App\Sports\Climbing\Controllers\AttemptsController::class, 'store']],
        ['POST', '/climbing/attempts/:id/delete',[\App\Sports\Climbing\Controllers\AttemptsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki wspinaczki',    'icon' => 'bi-triangle-fill', 'url' => 'climbing/results'],
        ['label' => 'Drogi klubowe',        'icon' => 'bi-list-columns',  'url' => 'climbing/routes'],
        ['label' => 'Biblioteka IFSC',      'icon' => 'bi-bookmark-star', 'url' => 'climbing/library'],
        ['label' => 'Próby (attempts)',     'icon' => 'bi-clipboard-pulse','url' => 'climbing/attempts'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
