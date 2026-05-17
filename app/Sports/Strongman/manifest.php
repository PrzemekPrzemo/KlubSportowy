<?php
// ============================================================
// Modul sportu: STRONGMAN (sport silowy)
// Promocja STUB → FULL w migracji 105 — używa wspólnych tabel
// `sport_strength_member` + `sport_strength_attempts` przez
// SportStrengthAttemptsController + SportStrengthPortalController.
// ============================================================

return [
    'key'        => 'strongman',
    'name'       => 'Strongman',
    'federation' => null,
    'status'     => 'full',
    'module'     => \App\Sports\Strongman\StrongmanModule::class,
    'features'   => ['attempts', 'events', 'weight_classes', 'live_scoreboard', 'demo-ready'],
    'routes'     => [],
    'nav'        => [
        ['label' => 'Podejścia', 'icon' => 'bi-shield-shaded', 'url' => 'club/sport/strongman/attempts'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
