<?php
return [
    'key'        => 'fencing',
    'name'       => 'Szermierka',
    'federation' => 'PZSzerm',
    'archetype'  => \App\Sports\Fencing\FencingArchetype::class,
    'module'     => \App\Sports\Fencing\FencingModule::class,
    'status'     => 'full',
    'features'   => [
        'results',
        'fencers',
        'weapons',
        'multi_weapon',
        'fie_rank',
        'pools',
        'de_bracket',
        'touches_scoring',
        'demo-ready',
    ],
    'routes' => [
        ['GET',  '/fencing/results',            [\App\Sports\Fencing\Controllers\ResultsController::class, 'index']],
        ['POST', '/fencing/results/store',      [\App\Sports\Fencing\Controllers\ResultsController::class, 'store']],
        ['POST', '/fencing/results/:id/delete', [\App\Sports\Fencing\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/fencing/fencers',            [\App\Sports\Fencing\Controllers\FencersController::class, 'index']],
        ['POST', '/fencing/fencers/store',      [\App\Sports\Fencing\Controllers\FencersController::class, 'store']],
        ['POST', '/fencing/fencers/:id/delete', [\App\Sports\Fencing\Controllers\FencersController::class, 'delete']],
        // Profil multi-weapon + pools (PARTIAL -> FULL)
        ['GET',  '/fencing/profile/:id',                [\App\Sports\Fencing\Controllers\SportFencingController::class, 'memberRecord']],
        ['POST', '/fencing/profile/:id/update',         [\App\Sports\Fencing\Controllers\SportFencingController::class, 'updateRecord']],
        ['GET',  '/fencing/pools/:id',                  [\App\Sports\Fencing\Controllers\SportFencingController::class, 'poolForm']],
        ['POST', '/fencing/pools/:id/store',            [\App\Sports\Fencing\Controllers\SportFencingController::class, 'storePool']],
    ],
    'nav' => [
        ['label' => 'Wyniki szermierki', 'icon' => 'bi-slash-lg', 'url' => 'fencing/results'],
        ['label' => 'Szermierze/ranking','icon' => 'bi-list-ol',  'url' => 'fencing/fencers'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
