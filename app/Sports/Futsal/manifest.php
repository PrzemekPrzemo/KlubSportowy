<?php
// ============================================================
// Modul sportu: FUTSAL (drozynowy, halowa pilka nozna)
// FULL functionality: teams + matches + stats + portal zawodnika
// ============================================================

return [
    'key'        => 'futsal',
    'name'       => 'Futsal',
    'federation' => 'PZPN',
    'archetype'  => \App\Sports\Futsal\FutsalArchetype::class,
    'team_sport' => true,
    'features'   => ['teams', 'players', 'matches', 'stats', 'cards', 'demo-ready'],
    'routes' => [
        // Teams
        ['GET',  '/futsal/teams',                    [\App\Sports\Futsal\Controllers\TeamsController::class,   'index']],
        ['POST', '/futsal/teams/store',              [\App\Sports\Futsal\Controllers\TeamsController::class,   'store']],
        ['POST', '/futsal/teams/:id/delete',         [\App\Sports\Futsal\Controllers\TeamsController::class,   'delete']],
        ['POST', '/futsal/teams/:id/player',         [\App\Sports\Futsal\Controllers\TeamsController::class,   'addPlayer']],
        ['POST', '/futsal/players/:id/delete',       [\App\Sports\Futsal\Controllers\TeamsController::class,   'removePlayer']],
        // Matches
        ['GET',  '/futsal/matches',                  [\App\Sports\Futsal\Controllers\MatchesController::class, 'index']],
        ['POST', '/futsal/matches/store',            [\App\Sports\Futsal\Controllers\MatchesController::class, 'store']],
        ['POST', '/futsal/matches/:id/delete',       [\App\Sports\Futsal\Controllers\MatchesController::class, 'delete']],
        ['GET',  '/futsal/matches/:id/stats',        [\App\Sports\Futsal\Controllers\MatchesController::class, 'statsForm']],
        ['POST', '/futsal/matches/:id/stats',        [\App\Sports\Futsal\Controllers\MatchesController::class, 'statsSave']],
        // Stats dashboard
        ['GET',  '/futsal/dashboard',                [\App\Sports\Futsal\Controllers\StatsController::class,   'dashboard']],
    ],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-people-fill',        'url' => 'futsal/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-flag',               'url' => 'futsal/matches'],
        ['label' => 'Statystyki', 'icon' => 'bi-bar-chart',          'url' => 'futsal/dashboard'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
