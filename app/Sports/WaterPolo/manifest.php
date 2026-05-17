<?php
// ============================================================
// Modul sportu: PILKA WODNA (Water Polo)
// FULL functionality: teams + matches + stats + portal zawodnika
// ============================================================

return [
    'key'        => 'water_polo',
    'name'       => 'Piłka wodna',
    'federation' => null,
    'archetype'  => \App\Sports\WaterPolo\WaterPoloArchetype::class,
    'team_sport' => true,
    'features'   => ['teams', 'players', 'matches', 'stats', 'exclusions', 'demo-ready'],
    'routes' => [
        // Teams
        ['GET',  '/water_polo/teams',                    [\App\Sports\WaterPolo\Controllers\TeamsController::class,   'index']],
        ['POST', '/water_polo/teams/store',              [\App\Sports\WaterPolo\Controllers\TeamsController::class,   'store']],
        ['POST', '/water_polo/teams/:id/delete',         [\App\Sports\WaterPolo\Controllers\TeamsController::class,   'delete']],
        ['POST', '/water_polo/teams/:id/player',         [\App\Sports\WaterPolo\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/water_polo/players/:id/delete',       [\App\Sports\WaterPolo\Controllers\TeamsController::class,   'removePlayer']],
        // Matches
        ['GET',  '/water_polo/matches',                  [\App\Sports\WaterPolo\Controllers\MatchesController::class, 'index']],
        ['POST', '/water_polo/matches/store',            [\App\Sports\WaterPolo\Controllers\MatchesController::class, 'store']],
        ['POST', '/water_polo/matches/:id/delete',       [\App\Sports\WaterPolo\Controllers\MatchesController::class, 'delete']],
        ['GET',  '/water_polo/matches/:id/stats',        [\App\Sports\WaterPolo\Controllers\MatchesController::class, 'statsForm']],
        ['POST', '/water_polo/matches/:id/stats',        [\App\Sports\WaterPolo\Controllers\MatchesController::class, 'statsSave']],
        // Dashboard
        ['GET',  '/water_polo/dashboard',                [\App\Sports\WaterPolo\Controllers\StatsController::class,   'dashboard']],
    ],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-droplet-half',    'url' => 'water_polo/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-droplet',         'url' => 'water_polo/matches'],
        ['label' => 'Statystyki', 'icon' => 'bi-bar-chart',       'url' => 'water_polo/dashboard'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
