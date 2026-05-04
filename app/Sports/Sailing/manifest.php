<?php
return [
    'key'        => 'sailing',
    'name'       => 'Żeglarstwo',
    'federation' => 'PZŻ',
    'features'   => ['boats', 'crew', 'races', 'licenses', 'handicap'],
    'routes' => [
        ['GET',  '/sailing/boats',              [\App\Sports\Sailing\Controllers\BoatsController::class, 'index']],
        ['POST', '/sailing/boats/store',        [\App\Sports\Sailing\Controllers\BoatsController::class, 'store']],
        ['POST', '/sailing/boats/:id/delete',   [\App\Sports\Sailing\Controllers\BoatsController::class, 'delete']],
        ['POST', '/sailing/boats/:id/crew/add', [\App\Sports\Sailing\Controllers\BoatsController::class, 'addCrew']],
        ['POST', '/sailing/boats/:id/crew/remove', [\App\Sports\Sailing\Controllers\BoatsController::class, 'removeCrew']],
        ['GET',  '/sailing/races',              [\App\Sports\Sailing\Controllers\RacesController::class, 'index']],
        ['POST', '/sailing/races/store',        [\App\Sports\Sailing\Controllers\RacesController::class, 'store']],
        ['POST', '/sailing/races/:id/delete',   [\App\Sports\Sailing\Controllers\RacesController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Łodzie / Jachty', 'icon' => 'bi-water',   'url' => 'sailing/boats'],
        ['label' => 'Regaty',          'icon' => 'bi-trophy',  'url' => 'sailing/races'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
