<?php
// ============================================================
// Modul sportu: E-SPORT — FULL (multi-game catalog + member profiles + leaderboardy).
// ============================================================

return [
    'key'        => 'esport',
    'name'       => 'E-sport',
    'federation' => 'PZEsp',
    'archetype'  => \App\Sports\Esport\EsportArchetype::class,
    'features'   => ['games-catalog', 'member-profiles', 'leaderboards', 'multi-game', 'demo-ready'],
    'routes' => [
        // Panel klubowy
        ['GET',  '/club/esport/games',                  [\App\Sports\Esport\Controllers\ClubEsportController::class, 'games']],
        ['POST', '/club/esport/games/store',            [\App\Sports\Esport\Controllers\ClubEsportController::class, 'storeGame']],
        ['POST', '/club/esport/games/:id/deactivate',   [\App\Sports\Esport\Controllers\ClubEsportController::class, 'deactivateGame']],
        ['GET',  '/club/esport/profiles',               [\App\Sports\Esport\Controllers\ClubEsportController::class, 'profiles']],
        ['GET',  '/club/esport/leaderboard/:gameCode',  [\App\Sports\Esport\Controllers\ClubEsportController::class, 'leaderboard']],

        // Portal zawodnika
        ['GET',  '/portal/esport/profiles',                  [\App\Sports\Esport\Controllers\PortalEsportController::class, 'myProfiles']],
        ['POST', '/portal/esport/profiles/save',             [\App\Sports\Esport\Controllers\PortalEsportController::class, 'saveProfile']],
        ['GET',  '/portal/esport/leaderboard/:gameCode',     [\App\Sports\Esport\Controllers\PortalEsportController::class, 'leaderboard']],
    ],
    'nav' => [
        ['label' => 'Katalog gier',     'icon' => 'bi-controller',   'url' => 'club/esport/games'],
        ['label' => 'Profile graczy',   'icon' => 'bi-people-fill',  'url' => 'club/esport/profiles'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
