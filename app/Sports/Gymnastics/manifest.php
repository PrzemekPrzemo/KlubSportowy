<?php
return [
    'key'        => 'gymnastics',
    'name'       => 'Gimnastyka',
    'federation' => 'PZG',
    'archetype'  => \App\Sports\Gymnastics\GymnasticsArchetype::class,
    'features'   => ['results', 'routines', 'apparatus', 'disciplines', 'minor_protection', 'd_score', 'e_score', 'judge_panel', 'demo-ready'],
    'routes' => [
        ['GET',  '/gymnastics/results',               [\App\Sports\Gymnastics\Controllers\ResultsController::class, 'index']],
        ['POST', '/gymnastics/results/store',         [\App\Sports\Gymnastics\Controllers\ResultsController::class, 'store']],
        ['POST', '/gymnastics/results/:id/delete',    [\App\Sports\Gymnastics\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/gymnastics/minors',                [\App\Sports\Gymnastics\Controllers\MinorController::class,   'index']],
        ['POST', '/gymnastics/minors/save',           [\App\Sports\Gymnastics\Controllers\MinorController::class,   'save']],
        // FULL: D-score + E-score per apparatus + judge panel
        ['GET',  '/gymnastics/scoring',               [\App\Sports\Gymnastics\Controllers\ScoringController::class, 'index']],
        ['POST', '/gymnastics/scoring/store',         [\App\Sports\Gymnastics\Controllers\ScoringController::class, 'store']],
        ['GET',  '/gymnastics/scoring/:id',           [\App\Sports\Gymnastics\Controllers\ScoringController::class, 'show']],
        ['POST', '/gymnastics/scoring/:id/judge',     [\App\Sports\Gymnastics\Controllers\ScoringController::class, 'addJudge']],
        ['POST', '/gymnastics/scoring/:id/delete',    [\App\Sports\Gymnastics\Controllers\ScoringController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki',          'icon' => 'bi-bar-chart',     'url' => 'gymnastics/results'],
        ['label' => 'D-score / E-score','icon' => 'bi-clipboard-data','url' => 'gymnastics/scoring'],
        ['label' => 'Zgody małoletnich','icon' => 'bi-shield-check', 'url' => 'gymnastics/minors'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
