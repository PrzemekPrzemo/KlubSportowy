<?php
// ============================================================
// Modul sportu: FITNESS (studio sport, FULL)
// Migracja 101 (studio_*) + dedykowany FitnessModule (klasy/karnety).
// ============================================================

return [
    'key'        => 'fitness',
    'name'       => 'Fitness',
    'federation' => null,
    'family'     => 'studio',
    'module'     => \App\Sports\Fitness\FitnessModule::class,
    'features'   => ['classes', 'passes', 'checkin'],
    'routes'     => [],
    'nav'        => [
        ['label' => 'Klasy', 'icon' => 'bi-calendar-week', 'url' => 'club/studio/fitness/schedules'],
        ['label' => 'Karnety', 'icon' => 'bi-card-checklist', 'url' => 'club/studio/fitness/pass-types'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
