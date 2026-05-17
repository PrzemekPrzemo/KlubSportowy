<?php
return [
    'key'        => 'dance_sport',
    'name'       => 'Taniec sportowy',
    'federation' => 'PZTS',
    'archetype'  => \App\Sports\DanceSport\DanceSportArchetype::class,
    'features'   => ['couples', 'results', 'classes', 'standard', 'latin', 'skating_system', 'finalist_promotion', 'judge_panel', 'demo-ready'],
    'routes' => [
        ['GET',  '/dance_sport/couples',              [\App\Sports\DanceSport\Controllers\CouplesController::class, 'index']],
        ['POST', '/dance_sport/couples/store',         [\App\Sports\DanceSport\Controllers\CouplesController::class, 'store']],
        ['POST', '/dance_sport/couples/:id/delete',    [\App\Sports\DanceSport\Controllers\CouplesController::class, 'delete']],
        ['GET',  '/dance_sport/results',               [\App\Sports\DanceSport\Controllers\ResultsController::class, 'index']],
        ['POST', '/dance_sport/results/store',         [\App\Sports\DanceSport\Controllers\ResultsController::class, 'store']],
        ['POST', '/dance_sport/results/:id/delete',    [\App\Sports\DanceSport\Controllers\ResultsController::class, 'delete']],
        // FULL: skating system + finalist promotion + judge panel
        ['GET',  '/dance_sport/scoring',               [\App\Sports\DanceSport\Controllers\ScoringController::class, 'index']],
        ['POST', '/dance_sport/scoring/store',         [\App\Sports\DanceSport\Controllers\ScoringController::class, 'store']],
        ['GET',  '/dance_sport/scoring/finalists',     [\App\Sports\DanceSport\Controllers\ScoringController::class, 'finalists']],
        ['GET',  '/dance_sport/scoring/:id',           [\App\Sports\DanceSport\Controllers\ScoringController::class, 'show']],
        ['POST', '/dance_sport/scoring/:id/judge',     [\App\Sports\DanceSport\Controllers\ScoringController::class, 'addJudge']],
        ['POST', '/dance_sport/scoring/:id/delete',    [\App\Sports\DanceSport\Controllers\ScoringController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Pary taneczne',   'icon' => 'bi-music-note-beamed', 'url' => 'dance_sport/couples'],
        ['label' => 'Wyniki zawodów',  'icon' => 'bi-trophy',            'url' => 'dance_sport/results'],
        ['label' => 'Skating system',  'icon' => 'bi-clipboard-data',    'url' => 'dance_sport/scoring'],
        ['label' => 'Finaliści',       'icon' => 'bi-award',             'url' => 'dance_sport/scoring/finalists'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
