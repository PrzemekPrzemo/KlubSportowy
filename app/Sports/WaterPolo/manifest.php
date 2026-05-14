<?php
// ============================================================
// Moduł sportu: PIŁKA WODNA (WaterPolo)
// Bootstrap — minimalny manifest, brak route'ów/nawigacji.
// Funkcjonalności biznesowe zostaną dodane w przyszłych PR-ach.
// ============================================================

return [
    'key'        => 'water_polo',
    'name'       => 'Piłka wodna',
    'federation' => null,
    'features'   => [],
    'routes'     => [],
    'nav'        => [],
    'migrations' => __DIR__ . '/migrations',
];
