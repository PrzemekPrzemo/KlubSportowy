<?php
// Migrate existing plaintext data to encrypted format.
// Run ONCE after setting up encryption key.
// Usage: php cli/encrypt-existing.php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = ROOT_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) require $file;
});

$cfg = file_exists(ROOT_PATH . '/config/app.local.php')
    ? require ROOT_PATH . '/config/app.local.php'
    : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

use App\Helpers\Database;
use App\Helpers\Encryption;

if (!Encryption::isConfigured()) {
    fwrite(STDERR, "ERROR: Encryption key not configured.\n");
    fwrite(STDERR, "Run: php cli/generate-key.php\n");
    exit(1);
}

$db = Database::pdo();
echo "[" . date('H:i:s') . "] Starting encryption migration...\n";

// Members: pesel, email, phone
$stmt = $db->query("SELECT id, pesel, email, phone FROM members WHERE pesel_hash IS NULL OR email_hash IS NULL");
$rows = $stmt->fetchAll();
echo "  Members to process: " . count($rows) . "\n";

$update = $db->prepare(
    "UPDATE members SET pesel = ?, pesel_hash = ?, email = ?, email_hash = ?, phone = ?, phone_hash = ? WHERE id = ?"
);

$count = 0;
foreach ($rows as $r) {
    $peselEnc  = $r['pesel'] ? Encryption::encrypt($r['pesel']) : null;
    $peselHash = $r['pesel'] ? Encryption::hash($r['pesel']) : null;
    $emailEnc  = $r['email'] ? Encryption::encrypt($r['email']) : null;
    $emailHash = $r['email'] ? Encryption::hash($r['email']) : null;
    $phoneEnc  = $r['phone'] ? Encryption::encrypt($r['phone']) : null;
    $phoneHash = $r['phone'] ? Encryption::hash($r['phone']) : null;

    $update->execute([$peselEnc, $peselHash, $emailEnc, $emailHash, $phoneEnc, $phoneHash, $r['id']]);
    $count++;
}
echo "  Encrypted {$count} member records.\n";

// Club settings: sensitive keys
$sensitiveKeys = ['smtp_pass_enc', 'sms_api_key', 'federation_%_pass', 'federation_%_api_key', 'stripe_secret_key', 'stripe_webhook_secret'];
$stmt = $db->query("SELECT club_id, `key`, value FROM club_settings WHERE `key` LIKE '%pass%' OR `key` LIKE '%api_key%' OR `key` LIKE '%secret%'");
$settings = $stmt->fetchAll();
echo "  Sensitive settings to encrypt: " . count($settings) . "\n";

$updateSetting = $db->prepare("UPDATE club_settings SET value = ? WHERE club_id = ? AND `key` = ?");
$sCount = 0;
foreach ($settings as $s) {
    if (empty($s['value'])) continue;
    // Skip if already looks encrypted (base64 with typical length)
    if (strlen($s['value']) > 50 && base64_decode($s['value'], true) !== false) continue;
    $encrypted = Encryption::encrypt($s['value']);
    $updateSetting->execute([$encrypted, $s['club_id'], $s['key']]);
    $sCount++;
}
echo "  Encrypted {$sCount} settings.\n";

echo "[" . date('H:i:s') . "] Done.\n";
