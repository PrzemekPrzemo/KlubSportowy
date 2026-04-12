<?php
return [
    'key'        => 'rollerskating',
    'name'       => 'Wrotkarstwo',
    'federation' => 'PZW',
    'features'   => ['equipment','times','disciplines'],
    'routes' => [
        ['GET',  '/rollerskating/equipment',              [\App\Sports\Rollerskating\Controllers\EquipmentController::class, 'index']],
        ['GET',  '/rollerskating/equipment/create',       [\App\Sports\Rollerskating\Controllers\EquipmentController::class, 'create']],
        ['POST', '/rollerskating/equipment/store',        [\App\Sports\Rollerskating\Controllers\EquipmentController::class, 'store']],
        ['POST', '/rollerskating/equipment/:id/delete',   [\App\Sports\Rollerskating\Controllers\EquipmentController::class, 'delete']],
        ['GET',  '/rollerskating/times',                  [\App\Sports\Rollerskating\Controllers\TimesController::class, 'index']],
        ['GET',  '/rollerskating/times/create',           [\App\Sports\Rollerskating\Controllers\TimesController::class, 'create']],
        ['POST', '/rollerskating/times/store',            [\App\Sports\Rollerskating\Controllers\TimesController::class, 'store']],
        ['POST', '/rollerskating/times/:id/delete',       [\App\Sports\Rollerskating\Controllers\TimesController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Sprzęt',  'icon' => 'bi-box-seam',  'url' => 'rollerskating/equipment'],
        ['label' => 'Pomiary', 'icon' => 'bi-stopwatch',  'url' => 'rollerskating/times'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
