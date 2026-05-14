<?php
// ============================================================
// Modul sportu: STRONGMAN (sport silowy)
// Bootstrap — minimalny manifest, bez routes/nav.
// Funkcjonalnosci biznesowe zostana dodane w przyszlych PR-ach.
// ============================================================

return [
    'key'        => 'strongman',
    'name'       => 'Strongman',
    'federation' => null,
    'features'   => [],
    'routes'     => [],
    'nav'        => [],
    'migrations' => __DIR__ . '/migrations',
];
