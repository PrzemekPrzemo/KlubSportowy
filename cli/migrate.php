<?php
// ============================================================
// cli/migrate.php — import database/schema.sql
// Usage: php cli/migrate.php
// ============================================================
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

$config = file_exists(ROOT_PATH . '/config/database.local.php')
    ? require ROOT_PATH . '/config/database.local.php'
    : require ROOT_PATH . '/config/database.php';

echo "Łączę z bazą: {$config['dbname']}@{$config['host']}\n";

$dsn = sprintf('mysql:host=%s;port=%d;charset=%s',
    $config['host'], $config['port'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$config['dbname']}`");

$sql = file_get_contents(ROOT_PATH . '/database/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "Nie mogę odczytać database/schema.sql\n");
    exit(1);
}

echo "Wykonuję schemat…\n";
try {
    $pdo->exec($sql);
    echo "OK — baza zainicjalizowana.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Błąd SQL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nDomyślny login: admin\n";
echo "Domyślne hasło: Admin1234!\n";
echo "(zmień po pierwszym logowaniu)\n";

echo "\nWskazówka: aby zaaplikować migracje na ISTNIEJĄCĄ bazę użyj:\n";
echo "  php cli/update.php\n";
