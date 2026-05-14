<?php
// ============================================================
// cli/update.php — runner migracji z trackingiem schema_migrations
// Usage:
//   php cli/update.php                  # zaaplikuj nowe migracje
//   php cli/update.php --dry-run        # pokaż co byłoby zaaplikowane
//   php cli/update.php --force          # re-aplikuj nawet zaaplikowane
//   php cli/update.php --only=055_inpost_shipping.sql
// ============================================================
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

// --------------------- ANSI helpers ---------------------
$useColor = function (): bool {
    if (getenv('NO_COLOR') !== false) return false;
    return function_exists('posix_isatty') ? @posix_isatty(STDOUT) : true;
};
$color = $useColor();
$ansi = function (string $code, string $text) use ($color): string {
    return $color ? "\033[{$code}m{$text}\033[0m" : $text;
};
$green   = fn(string $s) => $ansi('32', $s);
$red     = fn(string $s) => $ansi('31', $s);
$yellow  = fn(string $s) => $ansi('33', $s);
$cyan    = fn(string $s) => $ansi('36', $s);
$bold    = fn(string $s) => $ansi('1',  $s);
$dim     = fn(string $s) => $ansi('2',  $s);

// --------------------- Logging --------------------------
$logDir  = ROOT_PATH . '/storage/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
$logFile = $logDir . '/migrations.log';
$logLine = function (string $level, string $msg) use ($logFile): void {
    $line = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $msg);
    @file_put_contents($logFile, $line, FILE_APPEND);
};

// --------------------- Parse args -----------------------
$dryRun = false;
$force  = false;
$only   = null;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') $dryRun = true;
    elseif ($arg === '--force') $force = true;
    elseif (str_starts_with($arg, '--only=')) $only = substr($arg, 7);
    elseif ($arg === '-h' || $arg === '--help') {
        echo "Użycie: php cli/update.php [--dry-run] [--force] [--only=<plik>]\n";
        echo "  --dry-run     pokaż co zostanie zaaplikowane, nic nie wykonuj\n";
        echo "  --force       re-aplikuj migracje już oznaczone jako success\n";
        echo "  --only=PLIK   zaaplikuj tylko jeden plik (np. 055_inpost_shipping.sql\n";
        echo "                lub Sports/Football/001_football.sql)\n";
        exit(0);
    } else {
        fwrite(STDERR, "Nieznany argument: $arg\n");
        exit(1);
    }
}

echo $bold("→ ClubDesk migration runner\n");
if ($dryRun) echo $yellow("  Tryb: DRY-RUN (nic nie zostanie wykonane)\n");
if ($force)  echo $yellow("  Tryb: FORCE (re-aplikacja zaaplikowanych)\n");
if ($only)   echo $cyan ("  Tylko plik: $only\n");

// --------------------- DB connect -----------------------
$config = file_exists(ROOT_PATH . '/config/database.local.php')
    ? require ROOT_PATH . '/config/database.local.php'
    : require ROOT_PATH . '/config/database.php';

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'], $config['port'], $config['dbname'], $config['charset']);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
} catch (PDOException $e) {
    fwrite(STDERR, $red("✗ Połączenie z bazą '{$config['dbname']}@{$config['host']}' nieudane: ") . $e->getMessage() . "\n");
    $logLine('ERROR', 'DB connect failed: ' . $e->getMessage());
    exit(2);
}

echo $green("✓ Połączono z bazą {$config['dbname']}@{$config['host']}\n");
$logLine('INFO', "Update started (dry-run=" . ($dryRun ? '1' : '0') . ", force=" . ($force ? '1' : '0') . ", only=" . ($only ?? '-') . ")");

// --------------------- Ensure tracking table ------------
$tableExists = (function () use ($pdo, $config): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
    $stmt->execute([$config['dbname'], 'schema_migrations']);
    return (bool) $stmt->fetchColumn();
})();

if (!$tableExists) {
    echo $yellow("→ Tabela schema_migrations nie istnieje — tworzę bootstrap…\n");
    if (!$dryRun) {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS `schema_migrations` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration_file VARCHAR(255) NOT NULL UNIQUE,
                checksum VARCHAR(64) NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                duration_ms INT UNSIGNED NULL,
                status ENUM('success','partial','failed') NOT NULL DEFAULT 'success',
                error_message TEXT NULL,
                KEY idx_applied_at (applied_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
        $logLine('INFO', 'Created schema_migrations table');
    }
}

// --------------------- Discover migrations --------------
$rootDir   = ROOT_PATH . '/database/migrations';
$sportsDir = ROOT_PATH . '/app/Sports';

/** @var array<int, array{key:string,path:string}> $all */
$all = [];

foreach (glob($rootDir . '/*.sql') ?: [] as $path) {
    $all[] = ['key' => basename($path), 'path' => $path];
}

if (is_dir($sportsDir)) {
    foreach (glob($sportsDir . '/*/migrations/*.sql') ?: [] as $path) {
        // klucz: Sports/Football/001_football.sql
        $rel = substr($path, strlen(ROOT_PATH . '/app/') );
        $all[] = ['key' => $rel, 'path' => $path];
    }
}

// sort: root migrations posortowane alfabetycznie, potem sport per-sport po nazwie
usort($all, function (array $a, array $b): int {
    $aIsRoot = !str_contains($a['key'], '/');
    $bIsRoot = !str_contains($b['key'], '/');
    if ($aIsRoot !== $bIsRoot) return $aIsRoot ? -1 : 1;
    return strcmp($a['key'], $b['key']);
});

// --------------------- Bootstrap baseline ---------------
// Jeśli tabela schema_migrations dopiero powstała (lub jest pusta) ALE
// w bazie są tabele 'clubs' i 'members' → to legacy instalacja: oznacz
// wszystkie istniejące migracje jako pre-applied baseline.
$appliedCountStmt = $pdo->query("SELECT COUNT(*) FROM `schema_migrations`");
$appliedCount = (int) $appliedCountStmt->fetchColumn();

$coreTablesExist = (function () use ($pdo, $config): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN (\'clubs\', \'members\')'
    );
    $stmt->execute([$config['dbname']]);
    return ((int) $stmt->fetchColumn()) >= 2;
})();

if ($appliedCount === 0 && $coreTablesExist) {
    echo $yellow("→ Wykryto istniejącą instalację (tabele clubs/members obecne, brak trackingu)\n");
    echo $yellow("  Oznaczam wszystkie aktualne migracje jako baseline (status=success)…\n");
    if (!$dryRun) {
        $ins = $pdo->prepare(
            'INSERT IGNORE INTO `schema_migrations`
             (migration_file, checksum, applied_at, duration_ms, status, error_message)
             VALUES (?, ?, NOW(), 0, \'success\', \'baseline (pre-existing DB)\')'
        );
        foreach ($all as $m) {
            $contents = @file_get_contents($m['path']);
            $checksum = $contents !== false ? hash('sha256', $contents) : null;
            $ins->execute([$m['key'], $checksum]);
            echo "  " . $dim("• baseline " . $m['key']) . "\n";
        }
        $logLine('INFO', "Baseline registered: " . count($all) . " migrations");
    }
    echo $green("✓ Baseline ustawiony. Kolejne uruchomienia zaaplikują tylko NOWE migracje.\n");
    if (!$dryRun) {
        echo $cyan("\n→ Zakończono. Aby zaaplikować dalsze migracje (jeśli są), uruchom ponownie.\n");
        exit(0);
    }
} elseif ($appliedCount === 0 && !$coreTablesExist) {
    fwrite(STDERR, $red("✗ Baza jest pusta (brak tabeli clubs/members).\n"));
    fwrite(STDERR, "  Najpierw wykonaj świeżą instalację schematu: " . $bold("php cli/migrate.php") . "\n");
    $logLine('ERROR', 'Empty DB — schema.sql not applied yet');
    exit(3);
}

// --------------------- Load applied set -----------------
$applied = [];
$rows = $pdo->query("SELECT migration_file FROM `schema_migrations` WHERE status = 'success'");
foreach ($rows as $r) $applied[$r['migration_file']] = true;

// --------------------- Plan & execute -------------------
$toApply = [];
foreach ($all as $m) {
    if ($only !== null && $m['key'] !== $only) continue;
    if (!$force && isset($applied[$m['key']])) continue;
    $toApply[] = $m;
}

if ($only !== null && empty($toApply)) {
    fwrite(STDERR, $red("✗ Plik '$only' nie znaleziony w database/migrations ani app/Sports/*/migrations.\n"));
    $logLine('ERROR', "--only target not found: $only");
    exit(4);
}

echo "\n" . $bold("Plan: " . count($toApply) . " migracja(i) do aplikacji"
    . " (z " . count($all) . " łącznie, " . count($applied) . " już zaaplikowanych)\n");

$cApplied = 0; $cSkipped = count($all) - count($toApply); $cFailed = 0;

$insOk   = $pdo->prepare(
    'INSERT INTO `schema_migrations` (migration_file, checksum, applied_at, duration_ms, status)
     VALUES (?, ?, NOW(), ?, \'success\')
     ON DUPLICATE KEY UPDATE checksum=VALUES(checksum), applied_at=VALUES(applied_at),
        duration_ms=VALUES(duration_ms), status=\'success\', error_message=NULL'
);
$insFail = $pdo->prepare(
    'INSERT INTO `schema_migrations` (migration_file, checksum, applied_at, duration_ms, status, error_message)
     VALUES (?, ?, NOW(), ?, \'failed\', ?)
     ON DUPLICATE KEY UPDATE checksum=VALUES(checksum), applied_at=VALUES(applied_at),
        duration_ms=VALUES(duration_ms), status=\'failed\', error_message=VALUES(error_message)'
);

foreach ($toApply as $m) {
    $key  = $m['key'];
    $path = $m['path'];
    $sql  = @file_get_contents($path);
    if ($sql === false) {
        echo $red("✗ Nie mogę odczytać: $key\n");
        $logLine('ERROR', "Cannot read $path");
        $cFailed++;
        continue;
    }
    $checksum = hash('sha256', $sql);

    if ($dryRun) {
        echo "  " . $cyan("→ would apply $key") . " " . $dim("(" . strlen($sql) . " B, sha256=" . substr($checksum, 0, 8) . "…)") . "\n";
        continue;
    }

    $t0 = microtime(true);
    try {
        $pdo->exec($sql);
        $dur = (int) round((microtime(true) - $t0) * 1000);
        $insOk->execute([$key, $checksum, $dur]);
        echo "  " . $green("✓ applied  $key") . " " . $dim("({$dur} ms)") . "\n";
        $logLine('INFO', "Applied $key in {$dur} ms");
        $cApplied++;
    } catch (PDOException $e) {
        $dur = (int) round((microtime(true) - $t0) * 1000);
        $err = $e->getMessage();
        try {
            $insFail->execute([$key, $checksum, $dur, $err]);
        } catch (PDOException $e2) {
            $logLine('ERROR', "Nie udało się zapisać rekordu błędu dla $key: " . $e2->getMessage());
        }
        fwrite(STDERR, "  " . $red("✗ failed   $key") . " " . $dim("({$dur} ms)") . "\n");
        fwrite(STDERR, "    " . $red($err) . "\n");
        $logLine('ERROR', "Failed $key after {$dur} ms: $err");
        $cFailed++;
        // KONTYNUUJ — nie zatrzymuj
    }
}

// --------------------- Summary --------------------------
echo "\n" . $bold("Podsumowanie:") . "\n";
echo "  " . $green("Zaaplikowano: $cApplied") . "\n";
echo "  " . $dim  ("Pominięto:    $cSkipped") . "\n";
echo "  " . ($cFailed > 0 ? $red("Błędy:        $cFailed") : $dim("Błędy:        $cFailed")) . "\n";

$logLine('INFO', "Update finished: applied=$cApplied skipped=$cSkipped failed=$cFailed");

if ($dryRun) {
    echo "\n" . $yellow("Uruchom bez --dry-run aby zaaplikować zmiany.") . "\n";
}

exit($cFailed > 0 ? 1 : 0);
