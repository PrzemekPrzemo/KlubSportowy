<?php
return [
    'key'        => 'archery',
    'name'       => 'Łucznictwo',
    'federation' => 'PZŁucz',
    'archetype'  => \App\Sports\Archery\ArcheryArchetype::class,
    'features'   => ['equipment', 'scores', 'disciplines', 'recurve', 'compound', 'demo-ready'],
    'routes' => [
        ['GET',  '/archery/bows',               [\App\Sports\Archery\Controllers\BowsController::class,   'index']],
        ['POST', '/archery/bows/store',          [\App\Sports\Archery\Controllers\BowsController::class,   'store']],
        ['POST', '/archery/bows/:id/delete',     [\App\Sports\Archery\Controllers\BowsController::class,   'delete']],
        ['GET',  '/archery/scores',              [\App\Sports\Archery\Controllers\ScoresController::class, 'index']],
        ['POST', '/archery/scores/store',        [\App\Sports\Archery\Controllers\ScoresController::class, 'store']],
        ['POST', '/archery/scores/:id/delete',   [\App\Sports\Archery\Controllers\ScoresController::class, 'delete']],
    ],
    'nav' => [
        ['label' => 'Sprzęt (łuki)',      'icon' => 'bi-bullseye', 'url' => 'archery/bows'],
        ['label' => 'Wyniki strzelań',    'icon' => 'bi-trophy',   'url' => 'archery/scores'],
    ],
    'views_path' => __DIR__ . '/views',
    'migrations' => __DIR__ . '/migrations',
];
