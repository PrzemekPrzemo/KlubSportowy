<?php
return [
    'key'        => 'figureskating',
    'name'       => 'Łyżwiarstwo figurowe',
    'federation' => 'PZLF',
    'archetype'  => \App\Sports\FigureSkating\FigureSkatingArchetype::class,
    'features'   => ['results', 'disciplines', 'tes_pcs', 'sp_fs', 'demo-ready'],
    'routes' => [
        ['GET',  '/figureskating/results',            [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'index']],
        ['POST', '/figureskating/results/store',      [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'store']],
        ['GET',  '/figureskating/results/:id',        [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'show']],
        ['GET',  '/figureskating/results/:id/edit',   [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'edit']],
        ['POST', '/figureskating/results/:id/update', [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'update']],
        ['POST', '/figureskating/results/:id/delete', [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki figurowe', 'icon' => 'bi-star-half', 'url' => 'figureskating/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
