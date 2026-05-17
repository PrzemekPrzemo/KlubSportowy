<?php
return [
    'key'        => 'xcski',
    'name'       => 'Narciarstwo biegowe',
    'federation' => 'PZN',
    'status'     => 'full',
    'module'     => \App\Sports\XcSki\XcSkiModule::class,
    'archetype'  => \App\Sports\XcSki\XcSkiArchetype::class,
    'features'   => ['results', 'technique', 'distance', 'fis_points', 'timing_results', 'verified_results', 'demo-ready'],
    'routes' => [
        ['GET',  '/xcski/results',            [\App\Sports\XcSki\Controllers\ResultsController::class, 'index']],
        ['POST', '/xcski/results/store',      [\App\Sports\XcSki\Controllers\ResultsController::class, 'store']],
        ['GET',  '/xcski/results/:id',        [\App\Sports\XcSki\Controllers\ResultsController::class, 'show']],
        ['GET',  '/xcski/results/:id/edit',   [\App\Sports\XcSki\Controllers\ResultsController::class, 'edit']],
        ['POST', '/xcski/results/:id/update', [\App\Sports\XcSki\Controllers\ResultsController::class, 'update']],
        ['POST', '/xcski/results/:id/delete', [\App\Sports\XcSki\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki biegowe',        'icon' => 'bi-stopwatch', 'url' => 'xcski/results'],
        ['label' => 'Wyniki (zunifikowane)', 'icon' => 'bi-stopwatch', 'url' => 'club/sport/xcski/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
