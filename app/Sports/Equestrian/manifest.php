<?php
return [
    'key'        => 'equestrian',
    'name'       => 'Jeździectwo',
    'federation' => 'PZJ',
    'features'   => ['horses', 'owners', 'results', 'disciplines', 'dressage', 'jumping', 'pzj_passport', 'fei_passport'],
    'routes' => [
        ['GET',  '/equestrian/horses',              [\App\Sports\Equestrian\Controllers\HorsesController::class,  'index']],
        ['POST', '/equestrian/horses/store',         [\App\Sports\Equestrian\Controllers\HorsesController::class,  'store']],
        ['POST', '/equestrian/horses/:id/delete',    [\App\Sports\Equestrian\Controllers\HorsesController::class,  'delete']],
        ['GET',  '/equestrian/owners',               [\App\Sports\Equestrian\Controllers\OwnersController::class, 'index']],
        ['POST', '/equestrian/owners/store',         [\App\Sports\Equestrian\Controllers\OwnersController::class, 'store']],
        ['POST', '/equestrian/owners/:id/delete',    [\App\Sports\Equestrian\Controllers\OwnersController::class, 'delete']],
        ['GET',  '/equestrian/results',              [\App\Sports\Equestrian\Controllers\ResultsController::class, 'index']],
        ['POST', '/equestrian/results/store',        [\App\Sports\Equestrian\Controllers\ResultsController::class, 'store']],
        ['POST', '/equestrian/results/:id/delete',   [\App\Sports\Equestrian\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Konie',          'icon' => 'bi-compass',  'url' => 'equestrian/horses'],
        ['label' => 'Właściciele',    'icon' => 'bi-person-vcard', 'url' => 'equestrian/owners'],
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-trophy',   'url' => 'equestrian/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
