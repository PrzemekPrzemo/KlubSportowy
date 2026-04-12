<?php
// ============================================================
// Moduł sportu: KOSZYKÓWKA (PZKosz)
// ============================================================

return [
    'key'        => 'basketball',
    'name'       => 'Koszykówka',
    'federation' => 'PZKosz',
    'features'   => [
        'teams',
        'positions',      // PG, SG, SF, PF, C
        'matches',
        'player_stats',   // punkty, asysty, zbiórki, bloki
        'fouls',          // faule + kary
        'pzkosz_license',
    ],
    'routes' => [
        ['GET',  '/basketball/teams',              [\App\Sports\Basketball\Controllers\TeamsController::class, 'index']],
        ['GET',  '/basketball/teams/create',       [\App\Sports\Basketball\Controllers\TeamsController::class, 'create']],
        ['POST', '/basketball/teams/store',        [\App\Sports\Basketball\Controllers\TeamsController::class, 'store']],
        ['GET',  '/basketball/teams/:id/edit',     [\App\Sports\Basketball\Controllers\TeamsController::class, 'edit']],
        ['POST', '/basketball/teams/:id/update',   [\App\Sports\Basketball\Controllers\TeamsController::class, 'update']],
        ['POST', '/basketball/teams/:id/delete',   [\App\Sports\Basketball\Controllers\TeamsController::class, 'delete']],
        ['GET',  '/basketball/matches',            [\App\Sports\Basketball\Controllers\MatchesController::class, 'index']],
        ['GET',  '/basketball/matches/create',     [\App\Sports\Basketball\Controllers\MatchesController::class, 'create']],
        ['POST', '/basketball/matches/store',      [\App\Sports\Basketball\Controllers\MatchesController::class, 'store']],
        ['GET',  '/basketball/matches/:id',        [\App\Sports\Basketball\Controllers\MatchesController::class, 'show']],
        ['GET',  '/basketball/matches/:id/edit',   [\App\Sports\Basketball\Controllers\MatchesController::class, 'edit']],
        ['POST', '/basketball/matches/:id/update', [\App\Sports\Basketball\Controllers\MatchesController::class, 'update']],
        ['POST', '/basketball/matches/:id/delete', [\App\Sports\Basketball\Controllers\MatchesController::class, 'delete']],
        ['POST', '/basketball/matches/:id/stats',  [\App\Sports\Basketball\Controllers\MatchesController::class, 'addStats']],
        ['GET',  '/basketball/stats',              [\App\Sports\Basketball\Controllers\StatsController::class, 'index']],
    ],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-people',          'url' => 'basketball/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-record-circle',   'url' => 'basketball/matches'],
        ['label' => 'Statystyki', 'icon' => 'bi-bar-chart',       'url' => 'basketball/stats'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
