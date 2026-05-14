<?php
return [
    'key'        => 'handball',
    'name'       => 'Piłka ręczna',
    'federation' => 'ZPRP',
    'archetype'  => \App\Sports\Handball\HandballArchetype::class,
    'features'   => ['teams', 'players', 'matches', 'stats', 'positions', 'demo-ready'],
    'routes' => [
        ['GET',  '/handball/teams',              [\App\Sports\Handball\Controllers\TeamsController::class,   'index']],
        ['POST', '/handball/teams/store',        [\App\Sports\Handball\Controllers\TeamsController::class,   'store']],
        ['POST', '/handball/teams/:id/delete',   [\App\Sports\Handball\Controllers\TeamsController::class,   'delete']],
        ['POST', '/handball/teams/:id/player',   [\App\Sports\Handball\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/handball/players/:id/delete', [\App\Sports\Handball\Controllers\TeamsController::class,   'removePlayer']],
        ['GET',  '/handball/matches',            [\App\Sports\Handball\Controllers\MatchesController::class, 'index']],
        ['POST', '/handball/matches/store',      [\App\Sports\Handball\Controllers\MatchesController::class, 'store']],
        ['POST', '/handball/matches/:id/delete', [\App\Sports\Handball\Controllers\MatchesController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Drużyny piłki ręcznej', 'icon' => 'bi-people-fill', 'url' => 'handball/teams'],
        ['label' => 'Mecze',                 'icon' => 'bi-calendar3',    'url' => 'handball/matches'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
