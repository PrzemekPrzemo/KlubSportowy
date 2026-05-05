<?php
return [
    'key'        => 'mma',
    'name'       => 'MMA (Mixed Martial Arts)',
    'federation' => 'PZMMA',
    'archetype'  => \App\Sports\Mma\MmaArchetype::class,
    'features'   => ['fighters', 'results', 'methods', 'weight_classes', 'amateur_pro', 'demo-ready'],
    'routes' => [
        ['GET',  '/mma/fighters',            [\App\Sports\Mma\Controllers\FightersController::class, 'index']],
        ['POST', '/mma/fighters/store',      [\App\Sports\Mma\Controllers\FightersController::class, 'store']],
        ['POST', '/mma/fighters/:id/delete', [\App\Sports\Mma\Controllers\FightersController::class, 'delete']],
        ['GET',  '/mma/results',             [\App\Sports\Mma\Controllers\ResultsController::class,  'index']],
        ['POST', '/mma/results/store',       [\App\Sports\Mma\Controllers\ResultsController::class,  'store']],
        ['POST', '/mma/results/:id/delete',  [\App\Sports\Mma\Controllers\ResultsController::class,  'delete']],
    ],
    'nav' => [
        ['label' => 'Zawodnicy MMA', 'icon' => 'bi-person-bounding-box', 'url' => 'mma/fighters'],
        ['label' => 'Walki MMA',     'icon' => 'bi-trophy',              'url' => 'mma/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
