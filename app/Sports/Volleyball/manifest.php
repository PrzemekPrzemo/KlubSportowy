<?php
// ============================================================
// Moduł sportu: SIATKÓWKA (PZPS)
// ============================================================

return [
    'key'        => 'volleyball',
    'name'       => 'Siatkówka',
    'federation' => 'PZPS',
    'features'   => [
        'teams',
        'positions',      // atakujący, przyjmujący, środkowy, rozgrywający, libero
        'matches',
        'sets',           // statystyki per set
        'pzps_license',
    ],
    'routes' => [],
    'nav' => [
        ['label' => 'Drużyny', 'icon' => 'bi-people',    'url' => 'volleyball/teams'],
        ['label' => 'Mecze',   'icon' => 'bi-circle',    'url' => 'volleyball/matches'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
