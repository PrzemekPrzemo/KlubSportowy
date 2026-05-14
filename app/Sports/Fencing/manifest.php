<?php
return [
    'key'        => 'fencing',
    'name'       => 'Szermierka',
    'federation' => 'PZSzerm',
    'archetype'  => \App\Sports\Fencing\FencingArchetype::class,
    'features'   => ['results', 'fencers', 'weapons', 'ranking', 'fie_id', 'demo-ready'],
    'routes' => [
        ['GET',  '/fencing/results',            [\App\Sports\Fencing\Controllers\ResultsController::class, 'index']],
        ['POST', '/fencing/results/store',      [\App\Sports\Fencing\Controllers\ResultsController::class, 'store']],
        ['POST', '/fencing/results/:id/delete', [\App\Sports\Fencing\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/fencing/fencers',            [\App\Sports\Fencing\Controllers\FencersController::class, 'index']],
        ['POST', '/fencing/fencers/store',      [\App\Sports\Fencing\Controllers\FencersController::class, 'store']],
        ['POST', '/fencing/fencers/:id/delete', [\App\Sports\Fencing\Controllers\FencersController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki szermierki', 'icon' => 'bi-slash-lg', 'url' => 'fencing/results'],
        ['label' => 'Szermierze/ranking','icon' => 'bi-list-ol',  'url' => 'fencing/fencers'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
