<?php
return [
    'key'        => 'crossfit',
    'name'       => 'CrossFit / Trening funkcjonalny',
    'federation' => 'CrossFit Inc.',
    'features'   => ['wods', 'scores', 'personal_records', 'leaderboard', 'open_competition'],
    'routes' => [
        ['GET',  '/crossfit/wods',              [\App\Sports\CrossFit\Controllers\WodsController::class,            'index']],
        ['POST', '/crossfit/wods/store',        [\App\Sports\CrossFit\Controllers\WodsController::class,            'store']],
        ['POST', '/crossfit/wods/:id/delete',   [\App\Sports\CrossFit\Controllers\WodsController::class,            'delete']],
        ['POST', '/crossfit/wods/:id/score',    [\App\Sports\CrossFit\Controllers\WodsController::class,            'addScore']],
        ['GET',  '/crossfit/prs',               [\App\Sports\CrossFit\Controllers\PersonalRecordsController::class, 'index']],
        ['POST', '/crossfit/prs/store',         [\App\Sports\CrossFit\Controllers\PersonalRecordsController::class, 'store']],
        ['POST', '/crossfit/prs/:id/delete',    [\App\Sports\CrossFit\Controllers\PersonalRecordsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'WOD Library',  'icon' => 'bi-lightning-charge', 'url' => 'crossfit/wods'],
        ['label' => 'Rekordy (PR)', 'icon' => 'bi-bar-chart-line',   'url' => 'crossfit/prs'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
