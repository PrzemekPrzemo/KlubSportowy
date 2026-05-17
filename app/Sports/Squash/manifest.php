<?php
return [
    'key'        => 'squash',
    'name'       => 'Squash',
    'federation' => 'PSqA',
    'icon'       => 'bi-circle',
    'archetype'  => \App\Sports\Squash\SquashArchetype::class,
    'module'     => \App\Sports\Squash\SquashModule::class,
    'status'     => 'full',
    'features'   => ['results', 'rankings', 'par_match_stats', 'lets_strokes', 'match_history', 'portal_my_record', 'demo-ready'],
    'routes' => [
        ['GET',  '/squash/results',               [\App\Sports\Squash\Controllers\ResultsController::class,  'index']],
        ['POST', '/squash/results/store',          [\App\Sports\Squash\Controllers\ResultsController::class,  'store']],
        ['POST', '/squash/results/:id/delete',     [\App\Sports\Squash\Controllers\ResultsController::class,  'delete']],
        ['GET',  '/squash/rankings',               [\App\Sports\Squash\Controllers\RankingsController::class, 'index']],
        ['POST', '/squash/rankings/store',         [\App\Sports\Squash\Controllers\RankingsController::class, 'store']],
        ['POST', '/squash/rankings/:id/delete',    [\App\Sports\Squash\Controllers\RankingsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki meczy',     'icon' => 'bi-circle',       'url' => 'squash/results'],
        ['label' => 'Ranking PSA',      'icon' => 'bi-bar-chart',    'url' => 'squash/rankings'],
        ['label' => 'Mój profil',       'icon' => 'bi-person-vcard', 'url' => 'portal/sport/squash/my_record'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
