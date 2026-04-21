<?php
return [
    'key'        => 'tennis',
    'name'       => 'Tenis ziemny',
    'federation' => 'PZT',
    'features'   => ['matches', 'rankings', 'courts', 'surfaces', 'h2h'],
    'routes' => [
        ['GET',  '/tennis/matches',            [\App\Sports\Tennis\Controllers\MatchesController::class, 'index']],
        ['POST', '/tennis/matches/store',      [\App\Sports\Tennis\Controllers\MatchesController::class, 'store']],
        ['POST', '/tennis/matches/:id/delete', [\App\Sports\Tennis\Controllers\MatchesController::class, 'delete']],
        ['GET',  '/tennis/rankings',           [\App\Sports\Tennis\Controllers\RankingsController::class, 'index']],
    ],
    'nav' => [
        ['label' => 'Mecze tenisa',   'icon' => 'bi-bullseye', 'url' => 'tennis/matches'],
        ['label' => 'Ranking',        'icon' => 'bi-list-ol',  'url' => 'tennis/rankings'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
