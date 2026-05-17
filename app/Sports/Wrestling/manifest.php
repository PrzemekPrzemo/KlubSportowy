<?php
return [
    'key'        => 'wrestling',
    'name'       => 'Zapasy',
    'federation' => 'PZZ',
    'archetype'  => \App\Sports\Wrestling\WrestlingArchetype::class,
    'module'     => \App\Sports\Wrestling\WrestlingModule::class,
    'status'     => 'full',
    'features'   => [
        'results',
        'weight_categories',
        'styles',
        'technical_breakdown',
        'member_profile',
        'rank_points',
        'demo-ready',
    ],
    'routes' => [
        ['GET',  '/wrestling/results',            [\App\Sports\Wrestling\Controllers\ResultsController::class, 'index']],
        ['POST', '/wrestling/results/store',       [\App\Sports\Wrestling\Controllers\ResultsController::class, 'store']],
        ['GET',  '/wrestling/results/:id',         [\App\Sports\Wrestling\Controllers\ResultsController::class, 'show']],
        ['GET',  '/wrestling/results/:id/edit',    [\App\Sports\Wrestling\Controllers\ResultsController::class, 'edit']],
        ['POST', '/wrestling/results/:id/update',  [\App\Sports\Wrestling\Controllers\ResultsController::class, 'update']],
        ['POST', '/wrestling/results/:id/delete',  [\App\Sports\Wrestling\Controllers\ResultsController::class, 'delete']],
        // Kartoteka + technical breakdown (PARTIAL -> FULL)
        ['GET',  '/wrestling/profile/:id',                [\App\Sports\Wrestling\Controllers\SportWrestlingController::class, 'memberRecord']],
        ['POST', '/wrestling/profile/:id/update',         [\App\Sports\Wrestling\Controllers\SportWrestlingController::class, 'updateRecord']],
        ['GET',  '/wrestling/breakdown/:id',              [\App\Sports\Wrestling\Controllers\SportWrestlingController::class, 'breakdownForm']],
        ['POST', '/wrestling/breakdown/:id/store',        [\App\Sports\Wrestling\Controllers\SportWrestlingController::class, 'storeBreakdown']],
    ],
    'nav' => [
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-people-fill', 'url' => 'wrestling/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
