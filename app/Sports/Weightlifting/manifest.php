<?php
return [
    'key'        => 'weightlifting',
    'name'       => 'Podnoszenie ciężarów',
    'federation' => 'PKC',
    'features'   => ['results', 'weight_categories'],
    'routes' => [
        ['GET',  '/weightlifting/results',           [\App\Sports\Weightlifting\Controllers\ResultsController::class, 'index']],
        ['POST', '/weightlifting/results/store',      [\App\Sports\Weightlifting\Controllers\ResultsController::class, 'store']],
        ['POST', '/weightlifting/results/:id/delete', [\App\Sports\Weightlifting\Controllers\ResultsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki zawodów', 'icon' => 'bi-bar-chart-steps', 'url' => 'weightlifting/results'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
