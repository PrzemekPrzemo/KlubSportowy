<?php
return [
    'key'        => 'boxing',
    'name'       => 'Boks',
    'federation' => 'PZBoks',
    'archetype'  => \App\Sports\Boxing\BoxingArchetype::class,
    'module'     => \App\Sports\Boxing\BoxingModule::class,
    'status'     => 'full',
    'features'   => [
        'results',
        'medicals',
        'weight_classes',
        'fight_record',
        'license_levels',
        'weight_history',
        'amateur_pro',
        'demo-ready',
    ],
    'routes' => [
        ['GET',  '/boxing/results',             [\App\Sports\Boxing\Controllers\ResultsController::class,  'index']],
        ['POST', '/boxing/results/store',       [\App\Sports\Boxing\Controllers\ResultsController::class,  'store']],
        ['POST', '/boxing/results/:id/delete',  [\App\Sports\Boxing\Controllers\ResultsController::class,  'delete']],
        ['GET',  '/boxing/medicals',            [\App\Sports\Boxing\Controllers\MedicalsController::class, 'index']],
        ['POST', '/boxing/medicals/store',      [\App\Sports\Boxing\Controllers\MedicalsController::class, 'store']],
        ['POST', '/boxing/medicals/:id/delete', [\App\Sports\Boxing\Controllers\MedicalsController::class, 'delete']],
        // Kartoteka bokserska (PARTIAL -> FULL)
        ['GET',  '/boxing/record/:id',                [\App\Sports\Boxing\Controllers\SportBoxingController::class, 'memberRecord']],
        ['POST', '/boxing/record/:id/update',         [\App\Sports\Boxing\Controllers\SportBoxingController::class, 'updateRecord']],
        ['GET',  '/boxing/record/:id/weight',         [\App\Sports\Boxing\Controllers\SportBoxingController::class, 'weightHistory']],
        ['POST', '/boxing/record/:id/weight/add',     [\App\Sports\Boxing\Controllers\SportBoxingController::class, 'addWeightEntry']],
    ],
    'nav' => [
        ['label' => 'Walki (rekord W-L)', 'icon' => 'bi-trophy',      'url' => 'boxing/results'],
        ['label' => 'Badania lekarskie',  'icon' => 'bi-heart-pulse', 'url' => 'boxing/medicals'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
