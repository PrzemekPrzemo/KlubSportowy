<?php
return [
    'key'        => 'fieldhockey',
    'name'       => 'Hokej na trawie',
    'federation' => 'PZHnT',
    'features'   => ['teams', 'players', 'matches', 'events'],
    'routes' => [
        ['GET',  '/fieldhockey/teams',              [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'index']],
        ['POST', '/fieldhockey/teams/store',        [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'store']],
        ['POST', '/fieldhockey/teams/:id/delete',   [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'delete']],
        ['POST', '/fieldhockey/teams/:id/player',   [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/fieldhockey/players/:id/delete', [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'removePlayer']],
        ['GET',  '/fieldhockey/matches',            [\App\Sports\FieldHockey\Controllers\MatchesController::class, 'index']],
        ['POST', '/fieldhockey/matches/store',      [\App\Sports\FieldHockey\Controllers\MatchesController::class, 'store']],
        ['POST', '/fieldhockey/matches/:id/delete', [\App\Sports\FieldHockey\Controllers\MatchesController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Drużyny (trawa)', 'icon' => 'bi-people-fill', 'url' => 'fieldhockey/teams'],
        ['label' => 'Mecze',           'icon' => 'bi-trophy',       'url' => 'fieldhockey/matches'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
