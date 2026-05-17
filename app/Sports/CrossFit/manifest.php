<?php
return [
    'key'        => 'crossfit',
    'name'       => 'CrossFit / Trening funkcjonalny',
    'federation' => 'CrossFit Inc.',
    'archetype'  => \App\Sports\CrossFit\CrossFitArchetype::class,
    'features'   => ['wods', 'scores', 'personal_records', 'leaderboard', 'open_competition', 'wod_library_global', 'rx_scaled', 'demo-ready'],
    'routes' => [
        ['GET',  '/crossfit/wods',              [\App\Sports\CrossFit\Controllers\WodsController::class,            'index']],
        ['POST', '/crossfit/wods/store',        [\App\Sports\CrossFit\Controllers\WodsController::class,            'store']],
        ['POST', '/crossfit/wods/:id/delete',   [\App\Sports\CrossFit\Controllers\WodsController::class,            'delete']],
        ['POST', '/crossfit/wods/:id/score',    [\App\Sports\CrossFit\Controllers\WodsController::class,            'addScore']],
        ['GET',  '/crossfit/prs',               [\App\Sports\CrossFit\Controllers\PersonalRecordsController::class, 'index']],
        ['POST', '/crossfit/prs/store',         [\App\Sports\CrossFit\Controllers\PersonalRecordsController::class, 'store']],
        ['POST', '/crossfit/prs/:id/delete',    [\App\Sports\CrossFit\Controllers\PersonalRecordsController::class, 'delete']],
        // FULL: globalna biblioteka WOD (Murph/Cindy/...) + leaderboard z RX/scaled
        ['GET',  '/crossfit/library',           [\App\Sports\CrossFit\Controllers\LibraryController::class,        'index']],
        ['POST', '/crossfit/library/store',     [\App\Sports\CrossFit\Controllers\LibraryController::class,        'store']],
        ['POST', '/crossfit/library/:id/delete',[\App\Sports\CrossFit\Controllers\LibraryController::class,        'delete']],
        ['GET',  '/crossfit/leaderboard',       [\App\Sports\CrossFit\Controllers\LeaderboardController::class,    'index']],
        ['POST', '/crossfit/leaderboard/store', [\App\Sports\CrossFit\Controllers\LeaderboardController::class,    'storeResult']],
        ['POST', '/crossfit/leaderboard/:id/delete', [\App\Sports\CrossFit\Controllers\LeaderboardController::class, 'deleteResult']],
    ],
    'nav' => [
        ['label' => 'WOD Library',   'icon' => 'bi-lightning-charge', 'url' => 'crossfit/wods'],
        ['label' => 'Rekordy (PR)',  'icon' => 'bi-bar-chart-line',   'url' => 'crossfit/prs'],
        ['label' => 'Biblioteka WOD','icon' => 'bi-book',             'url' => 'crossfit/library'],
        ['label' => 'Leaderboard',   'icon' => 'bi-trophy',           'url' => 'crossfit/leaderboard'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
