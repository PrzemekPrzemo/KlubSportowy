<?php
// ============================================================
// cli/test_federation_apis.php — FederationExporter diagnostyka
//
// Iteruje wszystkie wspierane federacje (FederationExporterFactory::SUPPORTED)
// + wszystkie skonfigurowane per-klub i drukuje tabelę:
//
//   FEDERATION   STATUS         testConnection         sample fetchMemberStatus
//   PZSS         scraping       OK   (HTTP 200)        OK  (license XX/YY)
//   PZHL         scraping       OK   (HTTP 200)        unknown
//   PZPN         stub           ok   (sanity_check)    n/a
//   …
//
// Użycie:
//   php cli/test_federation_apis.php                    — globalny test
//   php cli/test_federation_apis.php --club=12          — klub konkretny
//   php cli/test_federation_apis.php --no-db            — bez DB (jedynie konstrukcja
//                                                          adapterów + testConnection)
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
if (file_exists($localApp)) {
    $cfg = require $localApp;
} elseif (file_exists(ROOT_PATH . '/config/app.php')) {
    $cfg = require ROOT_PATH . '/config/app.php';
} else {
    $cfg = [];
}
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

$args = array_slice($argv ?? [], 1);
$noDb = in_array('--no-db', $args, true);
$clubId = null;
foreach ($args as $a) {
    if (preg_match('/^--club=(\d+)$/', $a, $m)) $clubId = (int)$m[1];
}

echo "[" . date('Y-m-d H:i:s') . "] Federation API diagnostics\n";
echo "  PHP " . PHP_VERSION . " | mode: " . ($noDb ? 'no-db (smoke test)' : 'full') . "\n\n";

$rows = [];

// ----- Sekcja 1: testConnection() per supported federation (bez configu)
echo "== A) testConnection() per supported adapter (bez configu) ==\n";
foreach (\App\Helpers\Federations\FederationExporterFactory::supportedWithMetadata() as $code => $meta) {
    $exporter = \App\Helpers\Federations\FederationExporterFactory::forCode($code, []);
    if (!$exporter) {
        $rows[] = [$code, $meta['status'], 'no_adapter', '-'];
        continue;
    }
    try {
        $r = $exporter->testConnection();
        $ok = !empty($r['ok']);
        $msg = (string)($r['message'] ?? '');
        $rows[] = [$code, $exporter->adapterStatus(), ($ok ? 'OK' : 'FAIL') . ' [' . (string)($r['portal_http'] ?? '-') . ']', mb_substr($msg, 0, 70)];
    } catch (\Throwable $e) {
        $rows[] = [$code, $meta['status'], 'EXC', mb_substr($e->getMessage(), 0, 70)];
    }
}

// Drukuj jako tabelę
$widths = [10, 12, 18, 72];
$line = '+' . implode('+', array_map(fn($w) => str_repeat('-', $w + 2), $widths)) . "+\n";
echo $line;
printf("| %-{$widths[0]}s | %-{$widths[1]}s | %-{$widths[2]}s | %-{$widths[3]}s |\n",
    'Federation', 'Status', 'testConnection', 'Message');
echo $line;
foreach ($rows as $r) {
    printf("| %-{$widths[0]}s | %-{$widths[1]}s | %-{$widths[2]}s | %-{$widths[3]}s |\n",
        mb_substr($r[0], 0, $widths[0]),
        mb_substr($r[1], 0, $widths[1]),
        mb_substr($r[2], 0, $widths[2]),
        mb_substr($r[3], 0, $widths[3]));
}
echo $line;

// ----- Sekcja 2: sample fetchMemberStatus() z dummy ID (tylko dla scraping/login)
echo "\n== B) Sample fetchMemberStatus('SMOKE-TEST-ID') (publiczne portale) ==\n";
foreach (\App\Helpers\Federations\FederationExporterFactory::supportedWithMetadata() as $code => $meta) {
    if (!in_array($meta['status'], [
        \App\Helpers\Federations\FederationExporterInterface::STATUS_SCRAPING,
        \App\Helpers\Federations\FederationExporterInterface::STATUS_LOGIN,
    ], true)) {
        echo "  - {$code}: skip (status={$meta['status']})\n";
        continue;
    }
    $exporter = \App\Helpers\Federations\FederationExporterFactory::forCode($code, []);
    if (!$exporter) continue;
    try {
        $res = $exporter->fetchMemberStatus('SMOKE-TEST-ID');
        $status = (string)($res['status'] ?? '?');
        $hint = (string)($res['message'] ?? $res['verify_url'] ?? '');
        echo "  - {$code}: status={$status}  (" . mb_substr($hint, 0, 80) . ")\n";
    } catch (\Throwable $e) {
        echo "  - {$code}: EXC " . $e->getMessage() . "\n";
    }
}

// ----- Sekcja 3: per-klub credentials z DB
if ($noDb) {
    echo "\n(skipping DB-backed checks — --no-db)\n";
    exit(0);
}

echo "\n== C) Per-klub credentials z DB ==\n";
try {
    $db = \App\Helpers\Database::pdo();
} catch (\Throwable $e) {
    echo "  ! Brak DB: " . $e->getMessage() . " (use --no-db to skip)\n";
    exit(0);
}

$sql = "SELECT cfc.*, c.name AS club_name
        FROM club_federation_credentials cfc
        JOIN clubs c ON c.id = cfc.club_id
        WHERE cfc.is_active = 1" . ($clubId !== null ? " AND cfc.club_id = " . (int)$clubId : "") . "
        ORDER BY cfc.club_id, cfc.federation_code";
try {
    $rs = $db->query($sql)->fetchAll();
} catch (\Throwable $e) {
    echo "  ! Query failed: " . $e->getMessage() . "\n";
    exit(0);
}
if (!$rs) {
    echo "  → Brak aktywnych credentiali" . ($clubId ? " dla club_id={$clubId}" : "") . "\n";
    exit(0);
}

foreach ($rs as $cred) {
    $code = $cred['federation_code'];
    $club = $cred['club_name'];
    echo "  → Club#{$cred['club_id']} '{$club}', {$code}\n";
    $cfg = $cred;
    foreach (['api_username', 'api_password', 'api_token'] as $f) {
        $enc = $cred[$f . '_enc'] ?? null;
        try {
            $cfg[$f] = $enc ? \App\Helpers\Encryption::decrypt($enc) : null;
        } catch (\Throwable $e) {
            $cfg[$f] = null;
        }
    }
    $exporter = \App\Helpers\Federations\FederationExporterFactory::forCode($code, $cfg);
    if (!$exporter) { echo "    ! no adapter\n"; continue; }

    try {
        $r = $exporter->testConnection();
        echo "    testConnection: " . (!empty($r['ok']) ? 'OK' : 'FAIL') . " — " . ($r['message'] ?? '') . "\n";
    } catch (\Throwable $e) {
        echo "    testConnection: EXC " . $e->getMessage() . "\n";
    }

    // Sample fetchMemberStatus dla pierwszego członka z licencją (jeśli można)
    try {
        $stmt = $db->prepare("SELECT id, first_name, last_name FROM members WHERE club_id = ? AND status = 'aktywny' ORDER BY id LIMIT 1");
        $stmt->execute([(int)$cred['club_id']]);
        $m = $stmt->fetch();
        if ($m) {
            $sample = (string)($m['id'] ?? 'TEST');
            $res = $exporter->fetchMemberStatus($sample);
            echo "    fetchMemberStatus({$sample}): status=" . ($res['status'] ?? '?') . "\n";
        }
    } catch (\Throwable $e) {
        echo "    fetchMemberStatus: EXC " . $e->getMessage() . "\n";
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Done.\n";
