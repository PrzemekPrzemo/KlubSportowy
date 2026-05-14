<?php
return [
    'key'        => 'tennis',
    'name'       => 'Tenis ziemny',
    'federation' => 'PZT',
    'archetype'  => \App\Sports\Tennis\TennisArchetype::class,
    'features'   => ['matches', 'rankings', 'courts', 'surfaces', 'h2h', 'demo-ready'],
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
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
