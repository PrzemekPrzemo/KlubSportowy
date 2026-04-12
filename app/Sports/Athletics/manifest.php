<?php
return [
    'key'        => 'athletics',
    'name'       => 'Lekka atletyka',
    'federation' => 'PZLA',
    'features'   => ['disciplines','records','times','pzla_license'],
    'routes' => [
        ['GET',  '/athletics/records',             [\App\Sports\Athletics\Controllers\RecordsController::class, 'index']],
        ['GET',  '/athletics/records/create',      [\App\Sports\Athletics\Controllers\RecordsController::class, 'create']],
        ['POST', '/athletics/records/store',       [\App\Sports\Athletics\Controllers\RecordsController::class, 'store']],
        ['POST', '/athletics/records/:id/delete',  [\App\Sports\Athletics\Controllers\RecordsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Rekordy', 'icon' => 'bi-trophy',    'url' => 'athletics/records'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
