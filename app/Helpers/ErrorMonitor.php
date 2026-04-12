<?php

namespace App\Helpers;

/**
 * Lightweight error monitoring helper.
 * Sends exception data to an external monitoring service (e.g. Sentry)
 * via its HTTP ingestion API when sentry_dsn is configured.
 * Falls back to local log file when no DSN is set.
 */
class ErrorMonitor
{
    private static ?string $dsn = null;
    private static bool $initialized = false;

    /**
     * Initialize the error monitor with application config.
     */
    public static function init(array $appConfig): void
    {
        self::$dsn = $appConfig['sentry_dsn'] ?? null;
        self::$initialized = true;
    }

    /**
     * Capture and report a throwable.
     */
    public static function captureException(\Throwable $e): void
    {
        // Always log locally
        $logDir = ROOT_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logEntry = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n---\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @file_put_contents(
            $logDir . '/errors.log',
            $logEntry,
            FILE_APPEND | LOCK_EX
        );

        // Send to external monitoring if configured
        if (self::$dsn !== null && self::$dsn !== '') {
            self::sendToSentry($e);
        }
    }

    /**
     * Capture an arbitrary message.
     */
    public static function captureMessage(string $message, string $level = 'info'): void
    {
        $logDir = ROOT_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logEntry = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $message);
        @file_put_contents(
            $logDir . '/errors.log',
            $logEntry,
            FILE_APPEND | LOCK_EX
        );

        if (self::$dsn !== null && self::$dsn !== '') {
            self::sendMessageToSentry($message, $level);
        }
    }

    /**
     * Send exception to Sentry via store endpoint.
     */
    private static function sendToSentry(\Throwable $e): void
    {
        try {
            $parsed = self::parseDsn(self::$dsn);
            if ($parsed === null) return;

            $payload = [
                'event_id'  => str_replace('-', '', self::uuid4()),
                'timestamp' => date('Y-m-d\TH:i:s'),
                'level'     => 'error',
                'platform'  => 'php',
                'logger'    => 'klubsportowy',
                'server_name' => gethostname() ?: 'unknown',
                'exception' => [
                    'values' => [
                        [
                            'type'       => get_class($e),
                            'value'      => $e->getMessage(),
                            'stacktrace' => [
                                'frames' => self::buildFrames($e),
                            ],
                        ],
                    ],
                ],
                'tags' => [
                    'php_version' => PHP_VERSION,
                ],
                'extra' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ];

            $url = sprintf('%s/api/%s/store/', $parsed['base_url'], $parsed['project_id']);
            $auth = sprintf(
                'Sentry sentry_version=7, sentry_client=klubsportowy/1.0, sentry_key=%s',
                $parsed['public_key']
            );

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Sentry-Auth: ' . $auth,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable) {
            // Never let monitoring break the app
        }
    }

    private static function sendMessageToSentry(string $message, string $level): void
    {
        try {
            $parsed = self::parseDsn(self::$dsn);
            if ($parsed === null) return;

            $payload = [
                'event_id'  => str_replace('-', '', self::uuid4()),
                'timestamp' => date('Y-m-d\TH:i:s'),
                'level'     => $level,
                'platform'  => 'php',
                'logger'    => 'klubsportowy',
                'message'   => ['formatted' => $message],
            ];

            $url = sprintf('%s/api/%s/store/', $parsed['base_url'], $parsed['project_id']);
            $auth = sprintf(
                'Sentry sentry_version=7, sentry_client=klubsportowy/1.0, sentry_key=%s',
                $parsed['public_key']
            );

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Sentry-Auth: ' . $auth,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable) {}
    }

    /**
     * Parse Sentry DSN into components.
     * Format: https://<public_key>@<host>/<project_id>
     */
    private static function parseDsn(string $dsn): ?array
    {
        $parts = parse_url($dsn);
        if (!$parts || empty($parts['user']) || empty($parts['host'])) {
            return null;
        }

        $path = trim($parts['path'] ?? '', '/');
        $scheme = $parts['scheme'] ?? 'https';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return [
            'public_key' => $parts['user'],
            'project_id' => $path,
            'base_url'   => $scheme . '://' . $parts['host'] . $port,
        ];
    }

    private static function buildFrames(\Throwable $e): array
    {
        $frames = [];
        foreach ($e->getTrace() as $frame) {
            $frames[] = [
                'filename' => $frame['file'] ?? '<unknown>',
                'lineno'   => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '<unknown>',
            ];
        }
        // Sentry expects frames in reverse order (most recent last)
        return array_reverse($frames);
    }

    private static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
