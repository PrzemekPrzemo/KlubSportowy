<?php

namespace App\Helpers;

/**
 * Cache helper — tries Redis (phpredis), falls back to file-based cache.
 */
class Cache
{
    private static ?\Redis $redis = null;
    private static bool $redisChecked = false;

    /**
     * Get a cached value.
     */
    public static function get(string $key): mixed
    {
        $r = self::redis();
        if ($r !== null) {
            try {
                $val = $r->get($key);
                if ($val === false) {
                    return null;
                }
                $decoded = json_decode($val, true);
                return $decoded !== null ? $decoded : $val;
            } catch (\Throwable) {
                // fallback to file cache
            }
        }

        // File cache fallback
        $file = self::filePath($key);
        if (!file_exists($file)) {
            return null;
        }
        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }
        $cached = @unserialize($data);
        if ($cached === false || !is_array($cached)) {
            return null;
        }
        if (isset($cached['expires_at']) && $cached['expires_at'] < time()) {
            @unlink($file);
            return null;
        }
        return $cached['value'] ?? null;
    }

    /**
     * Set a cached value.
     */
    public static function set(string $key, mixed $value, int $ttl = 300): void
    {
        $r = self::redis();
        if ($r !== null) {
            try {
                $r->setex($key, $ttl, json_encode($value, JSON_UNESCAPED_UNICODE));
                return;
            } catch (\Throwable) {
                // fallback to file cache
            }
        }

        // File cache fallback
        $file = self::filePath($key);
        $dir  = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $data = serialize([
            'value'      => $value,
            'expires_at' => time() + $ttl,
        ]);
        @file_put_contents($file, $data, LOCK_EX);
    }

    /**
     * Delete a cached value.
     */
    public static function delete(string $key): void
    {
        $r = self::redis();
        if ($r !== null) {
            try {
                $r->del($key);
            } catch (\Throwable) {}
        }

        $file = self::filePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Flush all cached data.
     */
    public static function flush(): void
    {
        $r = self::redis();
        if ($r !== null) {
            try {
                $r->flushDB();
            } catch (\Throwable) {}
        }

        // Also flush file cache
        $dir = self::cacheDir();
        if (is_dir($dir)) {
            $files = glob($dir . '/*.cache');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
        }
    }

    /**
     * Try to connect to Redis (phpredis extension).
     * Returns null if not available or not configured.
     */
    private static function redis(): ?\Redis
    {
        if (self::$redisChecked) {
            return self::$redis;
        }
        self::$redisChecked = true;

        if (!extension_loaded('redis')) {
            return null;
        }

        $host = self::getSettingValue('redis_host');
        if (empty($host)) {
            // Check environment variable
            $host = getenv('REDIS_HOST') ?: null;
        }
        if (empty($host)) {
            return null;
        }

        $port = (int)(self::getSettingValue('redis_port') ?: (getenv('REDIS_PORT') ?: 6379));

        try {
            $redis = new \Redis();
            $redis->connect($host, $port, 2.0); // 2 second timeout
            $redis->ping();
            self::$redis = $redis;
        } catch (\Throwable) {
            self::$redis = null;
        }

        return self::$redis;
    }

    /**
     * Build file path for file-based cache.
     */
    private static function filePath(string $key): string
    {
        return self::cacheDir() . '/' . md5($key) . '.cache';
    }

    /**
     * Get cache directory path.
     */
    private static function cacheDir(): string
    {
        $dir = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /**
     * Read a global setting value from the settings table.
     */
    private static function getSettingValue(string $key): ?string
    {
        try {
            $db = Database::pdo();
            $stmt = $db->prepare("SELECT `value` FROM `settings` WHERE `key` = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (string)$val : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
