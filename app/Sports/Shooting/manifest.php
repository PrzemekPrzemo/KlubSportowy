<?php
// ============================================================
// Moduł sportu: STRZELECTWO (PZSS)
// ============================================================
// Aby włączyć ten moduł dla konkretnego klubu, administrator klubu
// aktywuje sport "shooting" w sekcji Sporty (/sports). Routy i nawigacja
// rejestrowane są globalnie przez SportModuleLoader, ale widoczne tylko
// kiedy klub ma tę sekcję aktywną (SportContext).
//
// Ten manifest stanowi wzorzec dla dodawania kolejnych modułów sportowych.
// Kolejne fazy: implementacja kontrolerów broni, amunicji, licencji PZSS,
// sędziów — wzorowane na odpowiednich modułach ShootingClubMng.

return [
    'key'        => 'shooting',
    'name'       => 'Strzelectwo',
    'federation' => 'PZSS',
    'features'   => [
        'weapons',        // broń klubowa i prywatna
        'ammo',           // ewidencja amunicji
        'pzss_license',   // licencje PZSS (patent, zawodnicza, trenerska)
        'judges',         // sędziowie i ich klasyfikacje
        'scorecards',     // metryczki strzałów (shot-by-shot)
    ],
    'routes' => [
        // Kolejne fazy: rejestracja tras własnych kontrolerów, np.:
        // ['GET',  '/shooting/weapons',           [\App\Sports\Shooting\Controllers\WeaponsController::class, 'index']],
        // ['GET',  '/shooting/weapons/create',    [\App\Sports\Shooting\Controllers\WeaponsController::class, 'create']],
        // ['POST', '/shooting/weapons/store',     [\App\Sports\Shooting\Controllers\WeaponsController::class, 'store']],
    ],
    'nav' => [
        ['label' => 'Broń klubowa',  'icon' => 'bi-bullseye',  'url' => 'shooting/weapons'],
        ['label' => 'Amunicja',      'icon' => 'bi-box-seam',  'url' => 'shooting/ammo'],
        ['label' => 'Licencje PZSS', 'icon' => 'bi-patch-check','url' => 'shooting/licenses'],
        ['label' => 'Sędziowie',     'icon' => 'bi-people-fill','url' => 'shooting/judges'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
