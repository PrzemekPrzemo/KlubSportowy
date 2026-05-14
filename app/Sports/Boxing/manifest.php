<?php
return [
    'key'        => 'boxing',
    'name'       => 'Boks',
    'federation' => 'PZBoks',
    'archetype'  => \App\Sports\Boxing\BoxingArchetype::class,
    'features'   => ['results', 'medicals', 'weight_classes', 'fight_record', 'amateur_pro', 'demo-ready'],
    'routes' => [
        ['GET',  '/boxing/results',             [\App\Sports\Boxing\Controllers\ResultsController::class,  'index']],
        ['POST', '/boxing/results/store',       [\App\Sports\Boxing\Controllers\ResultsController::class,  'store']],
        ['POST', '/boxing/results/:id/delete',  [\App\Sports\Boxing\Controllers\ResultsController::class,  'delete']],
        ['GET',  '/boxing/medicals',            [\App\Sports\Boxing\Controllers\MedicalsController::class, 'index']],
        ['POST', '/boxing/medicals/store',      [\App\Sports\Boxing\Controllers\MedicalsController::class, 'store']],
        ['POST', '/boxing/medicals/:id/delete', [\App\Sports\Boxing\Controllers\MedicalsController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Walki (rekord W-L)', 'icon' => 'bi-trophy',      'url' => 'boxing/results'],
        ['label' => 'Badania lekarskie',  'icon' => 'bi-heart-pulse', 'url' => 'boxing/medicals'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
