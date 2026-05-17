<?php
return [
    'key'        => 'sailing',
    'name'       => 'Żeglarstwo',
    'federation' => 'PZŻ',
    'archetype'  => \App\Sports\Sailing\SailingArchetype::class,
    'features'   => ['boats', 'crew', 'races', 'licenses', 'handicap', 'regatta_multi_race', 'low_point_scoring', 'drop_worst', 'isaf_profile', 'weather_log', 'demo-ready'],
    'routes' => [
        ['GET',  '/sailing/boats',              [\App\Sports\Sailing\Controllers\BoatsController::class, 'index']],
        ['POST', '/sailing/boats/store',        [\App\Sports\Sailing\Controllers\BoatsController::class, 'store']],
        ['POST', '/sailing/boats/:id/delete',   [\App\Sports\Sailing\Controllers\BoatsController::class, 'delete']],
        ['POST', '/sailing/boats/:id/crew/add', [\App\Sports\Sailing\Controllers\BoatsController::class, 'addCrew']],
        ['POST', '/sailing/boats/:id/crew/remove', [\App\Sports\Sailing\Controllers\BoatsController::class, 'removeCrew']],
        ['GET',  '/sailing/races',              [\App\Sports\Sailing\Controllers\RacesController::class, 'index']],
        ['POST', '/sailing/races/store',        [\App\Sports\Sailing\Controllers\RacesController::class, 'store']],
        ['POST', '/sailing/races/:id/delete',   [\App\Sports\Sailing\Controllers\RacesController::class, 'delete']],
        // FULL: regatta multi-race scoring + sailor profile (ISAF)
        ['GET',  '/sailing/regatta',            [\App\Sports\Sailing\Controllers\RegattaController::class, 'index']],
        ['POST', '/sailing/regatta/store',      [\App\Sports\Sailing\Controllers\RegattaController::class, 'store']],
        ['POST', '/sailing/regatta/:id/delete', [\App\Sports\Sailing\Controllers\RegattaController::class, 'delete']],
        ['GET',  '/sailing/sailor',             [\App\Sports\Sailing\Controllers\SailorProfileController::class, 'index']],
        ['POST', '/sailing/sailor/save',        [\App\Sports\Sailing\Controllers\SailorProfileController::class, 'save']],
    ],
    'nav' => [
        ['label' => 'Łodzie / Jachty',    'icon' => 'bi-water',           'url' => 'sailing/boats'],
        ['label' => 'Regaty',             'icon' => 'bi-trophy',          'url' => 'sailing/races'],
        ['label' => 'Multi-race + drop',  'icon' => 'bi-bar-chart-steps', 'url' => 'sailing/regatta'],
        ['label' => 'Profile ISAF',       'icon' => 'bi-person-vcard',    'url' => 'sailing/sailor'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
