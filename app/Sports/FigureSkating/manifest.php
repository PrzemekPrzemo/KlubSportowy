<?php
return [
    'key'        => 'figureskating',
    'name'       => 'Łyżwiarstwo figurowe',
    'federation' => 'PZLF',
    'features'   => ['results', 'disciplines', 'tes_pcs', 'sp_fs'],
    'routes' => [
        ['GET',  '/figureskating/results',            [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'index']],
        ['POST', '/figureskating/results/store',      [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'store']],
        ['POST', '/figureskating/results/:id/delete', [\App\Sports\FigureSkating\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki figurowe', 'icon' => 'bi-star-half', 'url' => 'figureskating/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
