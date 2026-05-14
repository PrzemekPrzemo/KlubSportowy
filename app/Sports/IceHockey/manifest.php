<?php
return [
    'key'        => 'icehockey',
    'name'       => 'Hokej na lodzie',
    'federation' => 'PZHL',
    'archetype'  => \App\Sports\IceHockey\IceHockeyArchetype::class,
    'features'   => ['teams', 'players', 'matches', 'periods', 'penalties', 'plus_minus', 'demo-ready'],
    'routes' => [
        ['GET',  '/icehockey/teams',              [\App\Sports\IceHockey\Controllers\TeamsController::class,   'index']],
        ['POST', '/icehockey/teams/store',        [\App\Sports\IceHockey\Controllers\TeamsController::class,   'store']],
        ['POST', '/icehockey/teams/:id/delete',   [\App\Sports\IceHockey\Controllers\TeamsController::class,   'delete']],
        ['POST', '/icehockey/teams/:id/player',   [\App\Sports\IceHockey\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/icehockey/players/:id/delete', [\App\Sports\IceHockey\Controllers\TeamsController::class,   'removePlayer']],
        ['GET',  '/icehockey/matches',            [\App\Sports\IceHockey\Controllers\MatchesController::class, 'index']],
        ['POST', '/icehockey/matches/store',      [\App\Sports\IceHockey\Controllers\MatchesController::class, 'store']],
        ['POST', '/icehockey/matches/:id/delete', [\App\Sports\IceHockey\Controllers\MatchesController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Drużyny hokejowe', 'icon' => 'bi-people-fill', 'url' => 'icehockey/teams'],
        ['label' => 'Mecze hokeja',     'icon' => 'bi-calendar3',   'url' => 'icehockey/matches'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
