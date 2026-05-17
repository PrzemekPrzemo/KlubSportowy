<?php
return [
    'key'        => 'golf',
    'name'       => 'Golf',
    'federation' => 'PZGolfa',
    'archetype'  => \App\Sports\Golf\GolfArchetype::class,
    'module'     => \App\Sports\Golf\GolfModule::class,
    'status'     => 'full',
    'features'   => ['handicap_whs', 'rounds', 'courses', 'tees', 'scorecard_tracking', 'handicap_pzg', 'course_database', 'portal_self_report', 'demo-ready'],
    'routes' => [
        ['GET',  '/golf/handicaps',            [\App\Sports\Golf\Controllers\HandicapsController::class, 'index']],
        ['POST', '/golf/handicaps/store',      [\App\Sports\Golf\Controllers\HandicapsController::class, 'store']],
        ['POST', '/golf/handicaps/:id/delete', [\App\Sports\Golf\Controllers\HandicapsController::class, 'delete']],
        ['GET',  '/golf/rounds',               [\App\Sports\Golf\Controllers\RoundsController::class,    'index']],
        ['POST', '/golf/rounds/store',         [\App\Sports\Golf\Controllers\RoundsController::class,    'store']],
        ['POST', '/golf/rounds/:id/delete',    [\App\Sports\Golf\Controllers\RoundsController::class,    'delete']],
    ],
    'nav' => [
        ['label' => 'Handicap WHS', 'icon' => 'bi-graph-up-arrow', 'url' => 'golf/handicaps'],
        ['label' => 'Rundy',        'icon' => 'bi-circle-half',    'url' => 'golf/rounds'],
        ['label' => 'Pola golfowe', 'icon' => 'bi-flag',           'url' => 'club/sport/golf/courses'],
        ['label' => 'Scorecardy',   'icon' => 'bi-check2-square',  'url' => 'club/sport/golf/scorecards'],
        ['label' => 'Mój profil',   'icon' => 'bi-person-vcard',   'url' => 'portal/sport/golf/my_record'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
