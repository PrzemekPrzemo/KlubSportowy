<?php
require_once __DIR__ . '/../bootstrap/php_version_check.php';
// Generate encryption key for AES-256-GCM
// Usage: php cli/generate-key.php
// Copy the output to config/encryption.local.php

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = ROOT_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) require $file;
});

$key = \App\Helpers\Encryption::generateKey();

echo "Generated encryption key:\n\n";
echo "  {$key}\n\n";
echo "Add to config/encryption.local.php:\n\n";
echo "<?php\nreturn [\n    'key'    => '{$key}',\n    'cipher' => 'aes-256-gcm',\n];\n\n";
echo "IMPORTANT: Store this key securely. Losing it means losing encrypted data.\n";
