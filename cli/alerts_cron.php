<?php
// ============================================================
// cli/alerts_cron.php — scan for expiring items and enqueue
// notifications (email + in-app)
// Usage: 0 6 * * * php /path/to/cli/alerts_cron.php
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

$vendor = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) require $vendor;

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Models\NotificationModel;

$pdo = Database::pdo();

echo "[" . date('Y-m-d H:i:s') . "] Alerts cron starting…\n";

// Parametry alertów z globalnych settings
$medDays = (int)($pdo->query("SELECT value FROM settings WHERE `key`='alert_medical_days'")->fetchColumn() ?: 30);
$licDays = (int)($pdo->query("SELECT value FROM settings WHERE `key`='alert_license_days'")->fetchColumn() ?: 60);

// ============================================================
// Badania lekarskie
// ============================================================
$sql = "SELECT me.*, m.first_name, m.last_name, m.email, c.name AS club_name,
               DATEDIFF(me.valid_until, CURDATE()) AS days
        FROM member_medical_exams me
        JOIN members m ON m.id = me.member_id
        JOIN clubs c   ON c.id = me.club_id
        WHERE me.valid_until = DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND m.status = 'aktywny'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$medDays]);
$rows = $stmt->fetchAll();
$count = 0;
foreach ($rows as $r) {
    if (!empty($r['email'])) {
        EmailService::queueFromTemplate(
            (int)$r['club_id'],
            'medical_expiry',
            $r['email'],
            [
                'first_name'  => $r['first_name'],
                'last_name'   => $r['last_name'],
                'club_name'   => $r['club_name'],
                'valid_until' => $r['valid_until'],
                'days'        => $r['days'],
            ],
            $r['first_name'] . ' ' . $r['last_name']
        );
        $count++;
    }
}
echo "  medical: {$count} alerts queued\n";

// ============================================================
// Licencje (generic member_licenses)
// ============================================================
$sql = "SELECT ml.*, m.first_name, m.last_name, m.email, c.name AS club_name,
               DATEDIFF(ml.valid_until, CURDATE()) AS days
        FROM member_licenses ml
        JOIN members m ON m.id = ml.member_id
        JOIN clubs c   ON c.id = ml.club_id
        WHERE ml.valid_until = DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND ml.status = 'aktywna'
          AND m.status = 'aktywny'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$licDays]);
$rows = $stmt->fetchAll();
$count = 0;
foreach ($rows as $r) {
    if (!empty($r['email'])) {
        EmailService::queueFromTemplate(
            (int)$r['club_id'],
            'license_expiry',
            $r['email'],
            [
                'first_name'     => $r['first_name'],
                'last_name'      => $r['last_name'],
                'club_name'      => $r['club_name'],
                'license_type'   => $r['license_type'],
                'license_number' => $r['license_number'],
                'valid_until'    => $r['valid_until'],
                'days'           => $r['days'],
            ],
            $r['first_name'] . ' ' . $r['last_name']
        );
        $count++;
    }
}
echo "  licenses: {$count} alerts queued\n";

// ============================================================
// In-app: superadmin powiadomienia dla wygasających badań
// ============================================================
$notif = new NotificationModel();
$stmt = $pdo->query(
    "SELECT me.club_id, COUNT(*) AS cnt
     FROM member_medical_exams me
     WHERE me.valid_until <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       AND me.valid_until >= CURDATE()
     GROUP BY me.club_id"
);
foreach ($stmt->fetchAll() as $r) {
    $notif->notify(
        (int)$r['club_id'],
        null,
        'medical_alert',
        "Wygasające badania: {$r['cnt']}",
        "Przypomnienie: {$r['cnt']} badań lekarskich wygasa w ciągu 30 dni",
        'medical'
    );
}

echo "[" . date('Y-m-d H:i:s') . "] Alerts cron finished\n";
