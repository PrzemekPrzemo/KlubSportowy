<?php

define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost:8080');
define('TESTING', true);

// Autoloader
spl_autoload_register(function (string $class): void {
    // App\\ namespace
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = ROOT_PATH . '/app/' . $relative . '.php';
        if (file_exists($file)) require $file;
    }
    // Tests\\ namespace
    $prefix = 'Tests\\';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = ROOT_PATH . '/tests/' . $relative . '.php';
        if (file_exists($file)) require $file;
    }
});

// Global helpers
require ROOT_PATH . '/app/Helpers/Helpers.php';
