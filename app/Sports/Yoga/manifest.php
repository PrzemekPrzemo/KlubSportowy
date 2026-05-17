<?php
// ============================================================
// Modul sportu: JOGA (studio sport, FULL)
// Migracja 101 (studio_*) + dedykowany YogaModule (klasy/karnety).
// ============================================================

return [
    'key'        => 'yoga',
    'name'       => 'Joga',
    'federation' => null,
    'family'     => 'studio',
    'module'     => \App\Sports\Yoga\YogaModule::class,
    'features'   => ['classes', 'passes', 'checkin'],
    'routes'     => [],
    'nav'        => [
        ['label' => 'Klasy', 'icon' => 'bi-calendar-week', 'url' => 'club/studio/yoga/schedules'],
        ['label' => 'Karnety', 'icon' => 'bi-card-checklist', 'url' => 'club/studio/yoga/pass-types'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
