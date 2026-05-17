<?php
return [
    'key'        => 'rugby',
    'name'       => 'Rugby',
    'federation' => 'PZRugby',
    'archetype'  => \App\Sports\Rugby\RugbyArchetype::class,
    'team_sport' => true,
    'features'   => ['teams', 'players', 'matches', 'events', 'stats', 'formats_15_7', 'demo-ready'],
    'routes' => [
        ['GET',  '/rugby/teams',              [\App\Sports\Rugby\Controllers\TeamsController::class,   'index']],
        ['POST', '/rugby/teams/store',        [\App\Sports\Rugby\Controllers\TeamsController::class,   'store']],
        ['POST', '/rugby/teams/:id/delete',   [\App\Sports\Rugby\Controllers\TeamsController::class,   'delete']],
        ['POST', '/rugby/teams/:id/player',   [\App\Sports\Rugby\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/rugby/players/:id/delete', [\App\Sports\Rugby\Controllers\TeamsController::class,   'removePlayer']],
        ['GET',  '/rugby/matches',            [\App\Sports\Rugby\Controllers\MatchesController::class, 'index']],
        ['POST', '/rugby/matches/store',      [\App\Sports\Rugby\Controllers\MatchesController::class, 'store']],
        ['POST', '/rugby/matches/:id/delete', [\App\Sports\Rugby\Controllers\MatchesController::class, 'delete']],
        // Stats (FULL)
        ['GET',  '/rugby/matches/:id/stats',  [\App\Sports\Rugby\Controllers\StatsController::class,   'statsForm']],
        ['POST', '/rugby/matches/:id/stats',  [\App\Sports\Rugby\Controllers\StatsController::class,   'statsSave']],
        ['GET',  '/rugby/dashboard',          [\App\Sports\Rugby\Controllers\StatsController::class,   'dashboard']],
    ],
    'nav' => [
        ['label' => 'Drużyny rugby', 'icon' => 'bi-people-fill', 'url' => 'rugby/teams'],
        ['label' => 'Mecze rugby',   'icon' => 'bi-trophy',       'url' => 'rugby/matches'],
        ['label' => 'Statystyki',    'icon' => 'bi-bar-chart',    'url' => 'rugby/dashboard'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
