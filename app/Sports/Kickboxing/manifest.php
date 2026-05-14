<?php
return [
    'key'        => 'kickboxing',
    'name'       => 'Kickboxing',
    'federation' => 'PZKick',
    'archetype'  => \App\Sports\Kickboxing\KickboxingArchetype::class,
    'features'   => ['belts', 'results', 'styles', 'weight_classes', 'demo-ready'],
    'routes' => [
        ['GET',  '/kickboxing/belts',              [\App\Sports\Kickboxing\Controllers\BeltsController::class,   'index']],
        ['POST', '/kickboxing/belts/store',        [\App\Sports\Kickboxing\Controllers\BeltsController::class,   'store']],
        ['POST', '/kickboxing/belts/:id/delete',   [\App\Sports\Kickboxing\Controllers\BeltsController::class,   'delete']],
        ['GET',  '/kickboxing/results',            [\App\Sports\Kickboxing\Controllers\ResultsController::class, 'index']],
        ['POST', '/kickboxing/results/store',      [\App\Sports\Kickboxing\Controllers\ResultsController::class, 'store']],
        ['POST', '/kickboxing/results/:id/delete', [\App\Sports\Kickboxing\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pasy kickboxing', 'icon' => 'bi-award',  'url' => 'kickboxing/belts'],
        ['label' => 'Walki',           'icon' => 'bi-trophy', 'url' => 'kickboxing/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
