<?php
// ============================================================
// Application configuration
// ============================================================

return [
    'app_name'    => 'KlubSportowy',
    'app_version' => '0.1.0',
    'debug'       => false,   // set true locally, false on production
    'timezone'    => 'Europe/Warsaw',
    'locale'      => 'pl_PL',
    'base_url'    => '',      // auto-detected in index.php; override if needed

    // Session
    'session_lifetime' => 7200,  // seconds

    // Paths
    'root_path'   => dirname(__DIR__),
    'view_path'   => dirname(__DIR__) . '/app/Views',
    'upload_path' => dirname(__DIR__) . '/storage/uploads',

    // Default locale for sports module labels
    'default_federation_country' => 'PL',
];
