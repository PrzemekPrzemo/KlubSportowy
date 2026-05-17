<?php
// ============================================================
// Modul sportu: CURLING (sport zimowy, druzynowy)
// FULL functionality: teams + matches + scoring per-end + portal
// ============================================================

return [
    'key'        => 'curling',
    'name'       => 'Curling',
    'federation' => 'PZCurl',
    'archetype'  => \App\Sports\Curling\CurlingArchetype::class,
    'team_sport' => true,
    'features'   => ['teams', 'players', 'matches', 'ends', 'hammer_alternation', 'demo-ready'],
    'routes' => [
        // Teams
        ['GET',  '/curling/teams',                    [\App\Sports\Curling\Controllers\TeamsController::class,   'index']],
        ['POST', '/curling/teams/store',              [\App\Sports\Curling\Controllers\TeamsController::class,   'store']],
        ['POST', '/curling/teams/:id/delete',         [\App\Sports\Curling\Controllers\TeamsController::class,   'delete']],
        ['POST', '/curling/teams/:id/player',         [\App\Sports\Curling\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/curling/players/:id/delete',       [\App\Sports\Curling\Controllers\TeamsController::class,   'removePlayer']],
        // Matches
        ['GET',  '/curling/matches',                  [\App\Sports\Curling\Controllers\MatchesController::class, 'index']],
        ['POST', '/curling/matches/store',            [\App\Sports\Curling\Controllers\MatchesController::class, 'store']],
        ['POST', '/curling/matches/:id/delete',       [\App\Sports\Curling\Controllers\MatchesController::class, 'delete']],
        ['GET',  '/curling/matches/:id/stats',        [\App\Sports\Curling\Controllers\MatchesController::class, 'statsForm']],
        ['POST', '/curling/matches/:id/stats',        [\App\Sports\Curling\Controllers\MatchesController::class, 'statsSave']],
        // Dashboard
        ['GET',  '/curling/dashboard',                [\App\Sports\Curling\Controllers\StatsController::class,   'dashboard']],
    ],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-snow',            'url' => 'curling/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-grid-3x3-gap',    'url' => 'curling/matches'],
        ['label' => 'Statystyki', 'icon' => 'bi-bar-chart',       'url' => 'curling/dashboard'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
