<?php
return [
    'key'        => 'bjj',
    'name'       => 'Brazilian Jiu-Jitsu',
    'federation' => 'PZBJJ',
    'archetype'  => \App\Sports\Bjj\BjjArchetype::class,
    'features'   => ['belts', 'results', 'weight_categories', 'gi_nogi', 'attendance', 'demo-ready'],
    'routes' => [
        ['GET',  '/bjj/belts',              [\App\Sports\Bjj\Controllers\BeltsController::class,   'index']],
        ['POST', '/bjj/belts/store',        [\App\Sports\Bjj\Controllers\BeltsController::class,   'store']],
        ['POST', '/bjj/belts/:id/delete',   [\App\Sports\Bjj\Controllers\BeltsController::class,   'delete']],
        ['GET',  '/bjj/results',            [\App\Sports\Bjj\Controllers\ResultsController::class, 'index']],
        ['POST', '/bjj/results/store',      [\App\Sports\Bjj\Controllers\ResultsController::class, 'store']],
        ['POST', '/bjj/results/:id/delete', [\App\Sports\Bjj\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pasy BJJ',       'icon' => 'bi-award',    'url' => 'bjj/belts'],
        ['label' => 'Wyniki walk',    'icon' => 'bi-trophy',   'url' => 'bjj/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
