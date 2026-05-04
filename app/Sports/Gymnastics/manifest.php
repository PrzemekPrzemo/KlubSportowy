<?php
return [
    'key'        => 'gymnastics',
    'name'       => 'Gimnastyka',
    'federation' => 'PZG',
    'features'   => ['results', 'routines', 'apparatus', 'disciplines', 'minor_protection'],
    'routes' => [
        ['GET',  '/gymnastics/results',               [\App\Sports\Gymnastics\Controllers\ResultsController::class, 'index']],
        ['POST', '/gymnastics/results/store',         [\App\Sports\Gymnastics\Controllers\ResultsController::class, 'store']],
        ['POST', '/gymnastics/results/:id/delete',    [\App\Sports\Gymnastics\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/gymnastics/minors',                [\App\Sports\Gymnastics\Controllers\MinorController::class,   'index']],
        ['POST', '/gymnastics/minors/save',           [\App\Sports\Gymnastics\Controllers\MinorController::class,   'save']],
    ],
    'nav' => [
        ['label' => 'Wyniki',          'icon' => 'bi-bar-chart',     'url' => 'gymnastics/results'],
        ['label' => 'Zgody małoletnich','icon' => 'bi-shield-check', 'url' => 'gymnastics/minors'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
