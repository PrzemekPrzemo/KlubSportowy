<?php
return [
    'key'        => 'padel',
    'name'       => 'Padel',
    'federation' => 'PZPadel',
    'archetype'  => \App\Sports\Padel\PadelArchetype::class,
    'features'   => ['pairs', 'courts', 'matches', 'reservations', 'rankings', 'demo-ready'],
    'routes' => [
        ['GET',  '/padel/pairs',                    [\App\Sports\Padel\Controllers\PairsController::class,        'index']],
        ['POST', '/padel/pairs/store',              [\App\Sports\Padel\Controllers\PairsController::class,        'store']],
        ['POST', '/padel/pairs/:id/delete',         [\App\Sports\Padel\Controllers\PairsController::class,        'delete']],
        ['GET',  '/padel/reservations',             [\App\Sports\Padel\Controllers\ReservationsController::class, 'index']],
        ['POST', '/padel/reservations/store',       [\App\Sports\Padel\Controllers\ReservationsController::class, 'store']],
        ['POST', '/padel/reservations/:id/confirm', [\App\Sports\Padel\Controllers\ReservationsController::class, 'confirm']],
        ['POST', '/padel/reservations/:id/cancel',  [\App\Sports\Padel\Controllers\ReservationsController::class, 'cancel']],
    ],
    'nav' => [
        ['label' => 'Pary / Ranking', 'icon' => 'bi-people',      'url' => 'padel/pairs'],
        ['label' => 'Rezerwacje',     'icon' => 'bi-calendar3',   'url' => 'padel/reservations'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
