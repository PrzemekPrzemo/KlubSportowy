<?php
return [
    'key'        => 'triathlon',
    'name'       => 'Triathlon',
    'federation' => 'PZTri',
    'status'     => 'full',
    'module'     => \App\Sports\Triathlon\TriathlonModule::class,
    'archetype'  => \App\Sports\Triathlon\TriathlonArchetype::class,
    'features'   => ['results', 'splits', 'age_groups', 'qualifications', 'distances', 'timing_results', 'verified_results', 'demo-ready'],
    'routes' => [
        ['GET',  '/triathlon/results',            [\App\Sports\Triathlon\Controllers\ResultsController::class,  'index']],
        ['POST', '/triathlon/results/store',      [\App\Sports\Triathlon\Controllers\ResultsController::class,  'store']],
        ['POST', '/triathlon/results/:id/delete', [\App\Sports\Triathlon\Controllers\ResultsController::class,  'delete']],
        ['GET',  '/triathlon/athletes',           [\App\Sports\Triathlon\Controllers\AthletesController::class, 'index']],
    ],
    'nav' => [
        ['label' => 'Wyniki',                'icon' => 'bi-stopwatch',    'url' => 'triathlon/results'],
        ['label' => 'Zawodnicy',             'icon' => 'bi-person-badge', 'url' => 'triathlon/athletes'],
        ['label' => 'Wyniki (zunifikowane)', 'icon' => 'bi-stopwatch',    'url' => 'club/sport/triathlon/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
