<?php
// ============================================================
// Modul sportu: TANIEC (Dance) — FULL (scoring/judging based).
// Sport bardziej ogolny niz DanceSport (taniec towarzyski).
// ============================================================

return [
    'key'        => 'dance',
    'name'       => 'Taniec',
    'federation' => 'PZTan',
    'archetype'  => \App\Sports\Dance\DanceArchetype::class,
    'features'   => ['styles-catalog', 'member-styles', 'performances', 'judge-scoring', 'demo-ready'],
    'routes' => [
        // Panel klubowy
        ['GET',  '/club/dance/styles',                  [\App\Sports\Dance\Controllers\ClubDanceController::class, 'styles']],
        ['POST', '/club/dance/styles/store',            [\App\Sports\Dance\Controllers\ClubDanceController::class, 'storeStyle']],
        ['POST', '/club/dance/styles/:id/deactivate',   [\App\Sports\Dance\Controllers\ClubDanceController::class, 'deactivateStyle']],
        ['GET',  '/club/dance/members',                 [\App\Sports\Dance\Controllers\ClubDanceController::class, 'members']],
        ['POST', '/club/dance/members/assign',          [\App\Sports\Dance\Controllers\ClubDanceController::class, 'assignMember']],
        ['GET',  '/club/dance/performances',            [\App\Sports\Dance\Controllers\ClubDanceController::class, 'performances']],
        ['POST', '/club/dance/performances/store',      [\App\Sports\Dance\Controllers\ClubDanceController::class, 'storePerformance']],
        ['POST', '/club/dance/performances/:id/judge',  [\App\Sports\Dance\Controllers\ClubDanceController::class, 'addJudgeScore']],

        // Portal zawodnika
        ['GET',  '/portal/dance/styles',         [\App\Sports\Dance\Controllers\PortalDanceController::class, 'myStyles']],
        ['POST', '/portal/dance/styles/save',    [\App\Sports\Dance\Controllers\PortalDanceController::class, 'saveStyle']],
        ['POST', '/portal/dance/styles/remove',  [\App\Sports\Dance\Controllers\PortalDanceController::class, 'removeStyle']],
    ],
    'nav' => [
        ['label' => 'Katalog stylow', 'icon' => 'bi-music-note-beamed', 'url' => 'club/dance/styles'],
        ['label' => 'Zawodnicy',      'icon' => 'bi-people',            'url' => 'club/dance/members'],
        ['label' => 'Wystepy',        'icon' => 'bi-trophy',            'url' => 'club/dance/performances'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
