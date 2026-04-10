<?php
// ============================================================
// Moduł sportu: PIŁKA NOŻNA (PZPN)
// ============================================================

return [
    'key'        => 'football',
    'name'       => 'Piłka nożna',
    'federation' => 'PZPN',
    'features'   => [
        'teams',          // drużyny klubu (seniorzy, juniorzy, etc.)
        'positions',      // pozycje zawodników (BR, OB, PM, NA ...)
        'matches',        // mecze ligowe i sparingi
        'cards',          // kartki żółte / czerwone / kontuzje
        'transfers',      // okienka transferowe / wypożyczenia
        'pzpn_license',   // licencje PZPN (zawodnicza, trenerska)
    ],
    'routes' => [
        // ['GET',  '/football/teams',             [\App\Sports\Football\Controllers\TeamsController::class, 'index']],
        // ['GET',  '/football/matches',           [\App\Sports\Football\Controllers\MatchesController::class, 'index']],
        // ['GET',  '/football/transfers',         [\App\Sports\Football\Controllers\TransfersController::class, 'index']],
    ],
    'nav' => [
        ['label' => 'Drużyny',     'icon' => 'bi-people',       'url' => 'football/teams'],
        ['label' => 'Mecze',       'icon' => 'bi-flag',         'url' => 'football/matches'],
        ['label' => 'Transfery',   'icon' => 'bi-arrow-left-right','url' => 'football/transfers'],
        ['label' => 'Licencje PZPN','icon' => 'bi-patch-check', 'url' => 'football/licenses'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
