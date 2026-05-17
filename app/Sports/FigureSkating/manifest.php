<?php
return [
    'key'        => 'figureskating',
    'name'       => 'Łyżwiarstwo figurowe',
    'federation' => 'PZLF',
    'archetype'  => \App\Sports\FigureSkating\FigureSkatingArchetype::class,
    'features'   => ['results', 'disciplines', 'tes_pcs', 'sp_fs', 'isu_scoring', 'judge_panel', 'seasons_best', 'demo-ready'],
    'routes' => [
        ['GET',  '/figureskating/results',            [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'index']],
        ['POST', '/figureskating/results/store',      [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'store']],
        ['GET',  '/figureskating/results/:id',        [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'show']],
        ['GET',  '/figureskating/results/:id/edit',   [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'edit']],
        ['POST', '/figureskating/results/:id/update', [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'update']],
        ['POST', '/figureskating/results/:id/delete', [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'delete']],
        // FULL: ISU TES+PCS scoring + judge panel
        ['GET',  '/figureskating/scoring',                [\App\Sports\FigureSkating\Controllers\ScoringController::class, 'index']],
        ['POST', '/figureskating/scoring/store',          [\App\Sports\FigureSkating\Controllers\ScoringController::class, 'store']],
        ['GET',  '/figureskating/scoring/:id',            [\App\Sports\FigureSkating\Controllers\ScoringController::class, 'show']],
        ['POST', '/figureskating/scoring/:id/judge',      [\App\Sports\FigureSkating\Controllers\ScoringController::class, 'addJudge']],
        ['POST', '/figureskating/scoring/:id/delete',     [\App\Sports\FigureSkating\Controllers\ScoringController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki figurowe',  'icon' => 'bi-star-half',     'url' => 'figureskating/results'],
        ['label' => 'ISU TES+PCS',      'icon' => 'bi-clipboard-data','url' => 'figureskating/scoring'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
