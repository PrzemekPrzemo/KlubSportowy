<?php
return [
    'key'        => 'badminton',
    'name'       => 'Badminton',
    'federation' => 'PZBad',
    'archetype'  => \App\Sports\Badminton\BadmintonArchetype::class,
    'module'     => \App\Sports\Badminton\BadmintonModule::class,
    'status'     => 'full',
    'features'   => ['results', 'match_set_stats', 'doubles_team_tracking', 'bwf_ranking_points', 'portal_my_record', 'demo-ready'],
    'routes' => [
        ['GET',  '/badminton/results',          [\App\Sports\Badminton\Controllers\ResultsController::class, 'index']],
        ['POST', '/badminton/results/store',    [\App\Sports\Badminton\Controllers\ResultsController::class, 'store']],
        ['GET',  '/badminton/results/:id',      [\App\Sports\Badminton\Controllers\ResultsController::class, 'show']],
        ['GET',  '/badminton/results/:id/edit', [\App\Sports\Badminton\Controllers\ResultsController::class, 'edit']],
        ['POST', '/badminton/results/:id/update',[\App\Sports\Badminton\Controllers\ResultsController::class, 'update']],
        ['POST', '/badminton/results/:id/delete',[\App\Sports\Badminton\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki zawodów',  'icon' => 'bi-feather',     'url' => 'badminton/results'],
        ['label' => 'Mój profil BWF',  'icon' => 'bi-person-vcard','url' => 'portal/sport/badminton/my_record'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
