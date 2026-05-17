<?php
return [
    'key'        => 'cycling',
    'name'       => 'Kolarstwo',
    'federation' => 'PZKol',
    'status'     => 'full',
    'module'     => \App\Sports\Cycling\CyclingModule::class,
    'archetype'  => \App\Sports\Cycling\CyclingArchetype::class,
    'features'   => ['results', 'ftp_tests', 'disciplines', 'uci_categories', 'power_watts', 'timing_results', 'verified_results', 'demo-ready'],
    'routes' => [
        ['GET',  '/cycling/results',            [\App\Sports\Cycling\Controllers\ResultsController::class, 'index']],
        ['POST', '/cycling/results/store',      [\App\Sports\Cycling\Controllers\ResultsController::class, 'store']],
        ['POST', '/cycling/results/:id/delete', [\App\Sports\Cycling\Controllers\ResultsController::class, 'delete']],
        ['GET',  '/cycling/ftp',                [\App\Sports\Cycling\Controllers\FtpController::class,     'index']],
        ['POST', '/cycling/ftp/store',          [\App\Sports\Cycling\Controllers\FtpController::class,     'store']],
        ['POST', '/cycling/ftp/:id/delete',     [\App\Sports\Cycling\Controllers\FtpController::class,     'delete']],
    ],
    'nav' => [
        ['label' => 'Wyniki kolarstwa',      'icon' => 'bi-bicycle',        'url' => 'cycling/results'],
        ['label' => 'Testy FTP',             'icon' => 'bi-lightning-fill', 'url' => 'cycling/ftp'],
        ['label' => 'Wyniki (zunifikowane)', 'icon' => 'bi-stopwatch',      'url' => 'club/sport/cycling/results'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
