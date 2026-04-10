<?php
// ============================================================
// Moduł sportu: LEKKA ATLETYKA (PZLA)
// ============================================================

return [
    'key'        => 'athletics',
    'name'       => 'Lekka atletyka',
    'federation' => 'PZLA',
    'features'   => [
        'disciplines',   // biegi, skoki, rzuty
        'records',       // rekordy życiowe
        'times',         // pomiary czasu
        'pzla_license',
    ],
    'routes' => [],
    'nav' => [
        ['label' => 'Rekordy', 'icon' => 'bi-trophy',    'url' => 'athletics/records'],
        ['label' => 'Pomiary', 'icon' => 'bi-stopwatch', 'url' => 'athletics/times'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
