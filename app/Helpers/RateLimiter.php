<?php

namespace App\Helpers;

class RateLimiter
{
    /**
     * Check if the IP is allowed to perform the action.
     * Returns true if OK, false if rate-limited / blocked.
     */
    public static function check(string $ip, string $action, int $maxAttempts = 5, int $windowMinutes = 15): bool
    {
        $db = Database::pdo();

        $stmt = $db->prepare(
            "SELECT attempts, last_attempt_at, blocked_until
             FROM rate_limits
             WHERE ip = ? AND action = ?
             LIMIT 1"
        );
        $stmt->execute([$ip, $action]);
        $row = $stmt->fetch();

        if (!$row) {
            return true;
        }

        // If explicitly blocked and block hasn't expired
        if ($row['blocked_until'] !== null && strtotime($row['blocked_until']) > time()) {
            return false;
        }

        // If block expired, allow (will be cleaned up or reset)
        if ($row['blocked_until'] !== null && strtotime($row['blocked_until']) <= time()) {
            // Block expired — reset
            self::reset($ip, $action);
            return true;
        }

        // Check if attempts within the window exceed max
        $windowStart = time() - ($windowMinutes * 60);
        if (strtotime($row['last_attempt_at']) < $windowStart) {
            // Outside window — old entry, allow
            return true;
        }

        return (int)$row['attempts'] < $maxAttempts;
    }

    /**
     * Record a failed attempt. Uses INSERT ... ON DUPLICATE KEY UPDATE.
     * Automatically blocks if maxAttempts exceeded.
     */
    public static function hit(string $ip, string $action, int $maxAttempts = 5, int $blockMinutes = 15): void
    {
        $db = Database::pdo();

        $stmt = $db->prepare(
            "INSERT INTO rate_limits (ip, action, attempts, last_attempt_at)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                attempts = attempts + 1,
                last_attempt_at = NOW()"
        );
        $stmt->execute([$ip, $action]);

        // Check if we need to set blocked_until
        $checkStmt = $db->prepare(
            "SELECT attempts FROM rate_limits WHERE ip = ? AND action = ? LIMIT 1"
        );
        $checkStmt->execute([$ip, $action]);
        $row = $checkStmt->fetch();

        if ($row && (int)$row['attempts'] >= $maxAttempts) {
            $blockUntil = date('Y-m-d H:i:s', time() + ($blockMinutes * 60));
            $updateStmt = $db->prepare(
                "UPDATE rate_limits SET blocked_until = ? WHERE ip = ? AND action = ?"
            );
            $updateStmt->execute([$blockUntil, $ip, $action]);
        }
    }

    /**
     * Reset rate limit entry (e.g., after successful login).
     */
    public static function reset(string $ip, string $action): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE ip = ? AND action = ?");
        $stmt->execute([$ip, $action]);
    }

    /**
     * Clean up old entries (older than 24 hours).
     */
    public static function cleanup(): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "DELETE FROM rate_limits WHERE last_attempt_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
