<?php

namespace App\Helpers;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $localConfig = ROOT_PATH . '/config/database.local.php';
        $config = file_exists($localConfig)
            ? require $localConfig
            : require ROOT_PATH . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        try {
            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        return self::$instance;
    }

    /** Shortcut */
    public static function pdo(): PDO
    {
        return self::getInstance();
    }

    /**
     * Wstrzyknij wlasny PDO (do testow z in-memory SQLite).
     * Uzywaj WYLACZNIE w testach — w produkcji nigdy.
     */
    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
