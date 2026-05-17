<?php
// ============================================================
// Modul sportu: PILATES (studio sport, FULL)
// Migracja 101 (studio_*) + dedykowany PilatesModule (klasy/karnety).
// ============================================================

return [
    'key'        => 'pilates',
    'name'       => 'Pilates',
    'federation' => null,
    'family'     => 'studio',
    'module'     => \App\Sports\Pilates\PilatesModule::class,
    'features'   => ['classes', 'passes', 'checkin'],
    'routes'     => [],
    'nav'        => [
        ['label' => 'Klasy', 'icon' => 'bi-calendar-week', 'url' => 'club/studio/pilates/schedules'],
        ['label' => 'Karnety', 'icon' => 'bi-card-checklist', 'url' => 'club/studio/pilates/pass-types'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
