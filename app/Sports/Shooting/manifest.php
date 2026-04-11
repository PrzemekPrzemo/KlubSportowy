<?php
// ============================================================
// Moduł sportu: STRZELECTWO (PZSS)
// ============================================================
// Rejestrowany przez SportModuleLoader — trasy i pozycje sidebara
// pojawiają się automatycznie gdy klub ma aktywną sekcję shooting.

return [
    'key'        => 'shooting',
    'name'       => 'Strzelectwo',
    'federation' => 'PZSS',
    'features'   => [
        'weapons',
        'ammo',
        'pzss_license',
        'judges',
        'scorecards',
    ],
    'routes' => [
        // Broń klubowa
        ['GET',  '/shooting/weapons',              [\App\Sports\Shooting\Controllers\WeaponsController::class, 'index']],
        ['GET',  '/shooting/weapons/create',       [\App\Sports\Shooting\Controllers\WeaponsController::class, 'create']],
        ['POST', '/shooting/weapons/store',        [\App\Sports\Shooting\Controllers\WeaponsController::class, 'store']],
        ['GET',  '/shooting/weapons/:id',          [\App\Sports\Shooting\Controllers\WeaponsController::class, 'show']],
        ['GET',  '/shooting/weapons/:id/edit',     [\App\Sports\Shooting\Controllers\WeaponsController::class, 'edit']],
        ['POST', '/shooting/weapons/:id/update',   [\App\Sports\Shooting\Controllers\WeaponsController::class, 'update']],
        ['POST', '/shooting/weapons/:id/delete',   [\App\Sports\Shooting\Controllers\WeaponsController::class, 'delete']],
        ['POST', '/shooting/weapons/:id/assign',   [\App\Sports\Shooting\Controllers\WeaponsController::class, 'assign']],
        ['POST', '/shooting/weapons/:id/return',   [\App\Sports\Shooting\Controllers\WeaponsController::class, 'returnWeapon']],

        // Amunicja
        ['GET',  '/shooting/ammo',                 [\App\Sports\Shooting\Controllers\AmmoController::class, 'index']],
        ['GET',  '/shooting/ammo/create',          [\App\Sports\Shooting\Controllers\AmmoController::class, 'create']],
        ['POST', '/shooting/ammo/store',           [\App\Sports\Shooting\Controllers\AmmoController::class, 'store']],
        ['GET',  '/shooting/ammo/:id',             [\App\Sports\Shooting\Controllers\AmmoController::class, 'show']],
        ['POST', '/shooting/ammo/:id/adjust',      [\App\Sports\Shooting\Controllers\AmmoController::class, 'adjust']],
        ['POST', '/shooting/ammo/:id/delete',      [\App\Sports\Shooting\Controllers\AmmoController::class, 'delete']],

        // Licencje PZSS
        ['GET',  '/shooting/licenses',             [\App\Sports\Shooting\Controllers\PzssLicensesController::class, 'index']],
        ['GET',  '/shooting/licenses/create',      [\App\Sports\Shooting\Controllers\PzssLicensesController::class, 'create']],
        ['POST', '/shooting/licenses/store',       [\App\Sports\Shooting\Controllers\PzssLicensesController::class, 'store']],
        ['GET',  '/shooting/licenses/:id/edit',    [\App\Sports\Shooting\Controllers\PzssLicensesController::class, 'edit']],
        ['POST', '/shooting/licenses/:id/update',  [\App\Sports\Shooting\Controllers\PzssLicensesController::class, 'update']],
        ['POST', '/shooting/licenses/:id/delete',  [\App\Sports\Shooting\Controllers\PzssLicensesController::class, 'delete']],

        // Sędziowie PZSS
        ['GET',  '/shooting/judges',               [\App\Sports\Shooting\Controllers\JudgesController::class, 'index']],
        ['GET',  '/shooting/judges/create',        [\App\Sports\Shooting\Controllers\JudgesController::class, 'create']],
        ['POST', '/shooting/judges/store',         [\App\Sports\Shooting\Controllers\JudgesController::class, 'store']],
        ['GET',  '/shooting/judges/:id/edit',      [\App\Sports\Shooting\Controllers\JudgesController::class, 'edit']],
        ['POST', '/shooting/judges/:id/update',    [\App\Sports\Shooting\Controllers\JudgesController::class, 'update']],
        ['POST', '/shooting/judges/:id/delete',    [\App\Sports\Shooting\Controllers\JudgesController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Broń klubowa',  'icon' => 'bi-bullseye',  'url' => 'shooting/weapons'],
        ['label' => 'Amunicja',      'icon' => 'bi-box-seam',  'url' => 'shooting/ammo'],
        ['label' => 'Licencje PZSS', 'icon' => 'bi-patch-check','url' => 'shooting/licenses'],
        ['label' => 'Sędziowie',     'icon' => 'bi-people-fill','url' => 'shooting/judges'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
