<?php
return [
    'key'        => 'aikido',
    'name'       => 'Aikido',
    'federation' => 'PZAI',
    'icon'       => 'bi-arrows-angle-expand',
    'archetype'  => \App\Sports\Aikido\AikidoArchetype::class,
    'features'   => ['belts', 'results', 'demo-ready'],
    'routes' => [
        ['GET',  '/aikido/belts',              [\App\Sports\Aikido\Controllers\BeltsController::class,   'index']],
        ['POST', '/aikido/belts/store',         [\App\Sports\Aikido\Controllers\BeltsController::class,   'store']],
        ['POST', '/aikido/belts/:id/delete',    [\App\Sports\Aikido\Controllers\BeltsController::class,   'delete']],
        ['GET',  '/aikido/results',             [\App\Sports\Aikido\Controllers\ResultsController::class, 'index']],
        ['POST', '/aikido/results/store',       [\App\Sports\Aikido\Controllers\ResultsController::class, 'store']],
        ['POST', '/aikido/results/:id/delete',  [\App\Sports\Aikido\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pasy (kyu/dan)',   'icon' => 'bi-arrows-angle-expand', 'url' => 'aikido/belts'],
        ['label' => 'Wyniki/Pokazy',    'icon' => 'bi-trophy',              'url' => 'aikido/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
