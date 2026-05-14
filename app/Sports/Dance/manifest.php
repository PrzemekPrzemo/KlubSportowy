<?php
// ============================================================
// Modul sportu: TANIEC (Dance — gimnastyka/grace, artystyczny)
// Bootstrap — minimalny manifest, bez routes/nav.
// Sport bardziej ogolny niz DanceSport (taniec towarzyski).
// ============================================================

return [
    'key'        => 'dance',
    'name'       => 'Taniec',
    'federation' => 'PZTan',
    'features'   => [],
    'routes'     => [],
    'nav'        => [],
    'migrations' => __DIR__ . '/migrations',
];
