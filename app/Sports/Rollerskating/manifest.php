<?php
// ============================================================
// Moduł sportu: WROTKARSTWO (PZW)
// ============================================================

return [
    'key'        => 'rollerskating',
    'name'       => 'Wrotkarstwo',
    'federation' => 'PZW',
    'features'   => [
        'equipment',    // wrotki, ochraniacze, kaski
        'times',        // pomiary czasu (speed skating)
        'disciplines',  // speed / freestyle / hockey / derby
    ],
    'routes' => [],
    'nav' => [
        ['label' => 'Sprzęt',    'icon' => 'bi-box-seam', 'url' => 'rollerskating/equipment'],
        ['label' => 'Wyniki',    'icon' => 'bi-stopwatch','url' => 'rollerskating/times'],
    ],
    'migrations' => __DIR__ . '/migrations',
];
