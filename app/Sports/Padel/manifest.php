<?php
return [
    'key'        => 'padel',
    'name'       => 'Padel',
    'federation' => 'PZPadel',
    'features'   => ['pairs', 'courts', 'matches', 'reservations', 'rankings'],
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
    'migrations' => __DIR__ . '/migrations',
];
