<?php
// Docker-specific database config (copy to database.local.php in container)
return [
    'host'     => getenv('DB_HOST') ?: 'db',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv('DB_DATABASE') ?: 'klubsportowy',
    'username' => getenv('DB_USERNAME') ?: 'klubsportowy',
    'password' => getenv('DB_PASSWORD') ?: 'secret',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
