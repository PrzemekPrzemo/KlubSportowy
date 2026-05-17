<?php
// ============================================================
// Modul sportu: KAJAKARSTWO (Canoeing) — FULL (timing-based: sprint + slalom).
// ============================================================

return [
    'key'        => 'canoeing',
    'name'       => 'Kajakarstwo',
    'federation' => 'PZKaj',
    'archetype'  => \App\Sports\Canoeing\CanoeingArchetype::class,
    'features'   => ['boat-classes', 'race-results', 'timing', 'rankings', 'demo-ready'],
    'routes' => [
        // Panel klubowy
        ['GET',  '/club/canoeing/members',        [\App\Sports\Canoeing\Controllers\ClubCanoeingController::class, 'members']],
        ['POST', '/club/canoeing/members/save',   [\App\Sports\Canoeing\Controllers\ClubCanoeingController::class, 'saveMember']],
        ['GET',  '/club/canoeing/results',        [\App\Sports\Canoeing\Controllers\ClubCanoeingController::class, 'results']],
        ['POST', '/club/canoeing/results/store',  [\App\Sports\Canoeing\Controllers\ClubCanoeingController::class, 'storeResult']],

        // Portal zawodnika
        ['GET',  '/portal/canoeing/me',           [\App\Sports\Canoeing\Controllers\PortalCanoeingController::class, 'me']],
    ],
    'nav' => [
        ['label' => 'Zawodnicy',        'icon' => 'bi-water',          'url' => 'club/canoeing/members'],
        ['label' => 'Wyniki wyscigow',  'icon' => 'bi-stopwatch-fill', 'url' => 'club/canoeing/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
