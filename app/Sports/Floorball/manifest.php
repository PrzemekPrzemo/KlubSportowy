<?php
return [
    'key'        => 'floorball',
    'name'       => 'Floorball (Unihokej)',
    'federation' => 'PFUF',
    'archetype'  => \App\Sports\Floorball\FloorballArchetype::class,
    'team_sport' => true,
    'features'   => ['teams', 'players', 'matches', 'stats', 'penalties', 'demo-ready'],
    'routes' => [
        ['GET',  '/floorball/teams',                    [\App\Sports\Floorball\Controllers\TeamsController::class,   'index']],
        ['POST', '/floorball/teams/store',              [\App\Sports\Floorball\Controllers\TeamsController::class,   'store']],
        ['POST', '/floorball/teams/:id/delete',         [\App\Sports\Floorball\Controllers\TeamsController::class,   'delete']],
        ['POST', '/floorball/teams/:id/player/add',     [\App\Sports\Floorball\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/floorball/teams/:id/player/remove',  [\App\Sports\Floorball\Controllers\TeamsController::class,   'removePlayer']],
        ['GET',  '/floorball/matches',                  [\App\Sports\Floorball\Controllers\MatchesController::class, 'index']],
        ['POST', '/floorball/matches/store',            [\App\Sports\Floorball\Controllers\MatchesController::class, 'store']],
        ['POST', '/floorball/matches/:id/result',       [\App\Sports\Floorball\Controllers\MatchesController::class, 'saveResult']],
        ['POST', '/floorball/matches/:id/delete',       [\App\Sports\Floorball\Controllers\MatchesController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-people-fill', 'url' => 'floorball/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-calendar2-check', 'url' => 'floorball/matches'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
