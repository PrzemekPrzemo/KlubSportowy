<?php
// ============================================================
// Database configuration
// ============================================================
// Copy to config/database.local.php and fill in production values.
// config/database.local.php is git-ignored.

return [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'klubsportowy',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
