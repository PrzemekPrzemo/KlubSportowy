<?php
return [
    'key'        => 'fieldhockey',
    'name'       => 'Hokej na trawie',
    'federation' => 'PZHT',
    'archetype'  => \App\Sports\FieldHockey\FieldHockeyArchetype::class,
    'team_sport' => true,
    'features'   => ['teams', 'players', 'matches', 'events', 'stats', 'cards', 'demo-ready'],
    'routes' => [
        ['GET',  '/fieldhockey/teams',              [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'index']],
        ['POST', '/fieldhockey/teams/store',        [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'store']],
        ['POST', '/fieldhockey/teams/:id/delete',   [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'delete']],
        ['POST', '/fieldhockey/teams/:id/player',   [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/fieldhockey/players/:id/delete', [\App\Sports\FieldHockey\Controllers\TeamsController::class,   'removePlayer']],
        ['GET',  '/fieldhockey/matches',            [\App\Sports\FieldHockey\Controllers\MatchesController::class, 'index']],
        ['POST', '/fieldhockey/matches/store',      [\App\Sports\FieldHockey\Controllers\MatchesController::class, 'store']],
        ['POST', '/fieldhockey/matches/:id/delete', [\App\Sports\FieldHockey\Controllers\MatchesController::class, 'delete']],
        // Stats (FULL)
        ['GET',  '/fieldhockey/matches/:id/stats',  [\App\Sports\FieldHockey\Controllers\StatsController::class,   'statsForm']],
        ['POST', '/fieldhockey/matches/:id/stats',  [\App\Sports\FieldHockey\Controllers\StatsController::class,   'statsSave']],
        ['GET',  '/fieldhockey/dashboard',          [\App\Sports\FieldHockey\Controllers\StatsController::class,   'dashboard']],
    ],
    'nav' => [
        ['label' => 'Drużyny (trawa)', 'icon' => 'bi-people-fill', 'url' => 'fieldhockey/teams'],
        ['label' => 'Mecze',           'icon' => 'bi-trophy',       'url' => 'fieldhockey/matches'],
        ['label' => 'Statystyki',      'icon' => 'bi-bar-chart',    'url' => 'fieldhockey/dashboard'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
