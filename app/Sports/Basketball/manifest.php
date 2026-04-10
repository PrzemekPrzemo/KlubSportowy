<?php
// ============================================================
// Moduł sportu: KOSZYKÓWKA (PZKosz)
// ============================================================

return [
    'key'        => 'basketball',
    'name'       => 'Koszykówka',
    'federation' => 'PZKosz',
    'features'   => [
        'teams',
        'positions',      // PG, SG, SF, PF, C
        'matches',
        'player_stats',   // punkty, asysty, zbiórki, bloki
        'fouls',          // faule + kary
        'pzkosz_license',
    ],
    'routes' => [],
    'nav' => [
        ['label' => 'Drużyny',    'icon' => 'bi-people',          'url' => 'basketball/teams'],
        ['label' => 'Mecze',      'icon' => 'bi-record-circle',   'url' => 'basketball/matches'],
        ['label' => 'Statystyki', 'icon' => 'bi-bar-chart',       'url' => 'basketball/stats'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
