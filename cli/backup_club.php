<?php
// ============================================================
// cli/backup_club.php — per-tenant backup (mysqldump --where club_id=N)
// Usage:
//   php cli/backup_club.php <clubId>          # backup jednego klubu
//   php cli/backup_club.php --all             # backup wszystkich aktywnych
//   php cli/backup_club.php <clubId> --gzip   # spakuj gzipem (default: tak)
//
// Wzorzec inspirowany Hovera (Provisioner z TenantDumpSchemaCommand) —
// tam dump jest natywny (dedykowana baza). U nas (shared schema) robimy
// per-table mysqldump z --where="club_id=N" tylko dla tabel klubowych.
// Wynik: kazdy dump zawiera WYLACZNIE dane jednego klubu.
//
// Output: storage/backups/club_<id>_<timestamp>.sql[.gz]
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

// --- parse args -------------------------------------------------------
$clubId = null;
$all    = false;
$gzip   = true;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--all')      $all = true;
    elseif ($arg === '--no-gzip') $gzip = false;
    elseif ($arg === '--gzip') $gzip = true;
    elseif (ctype_digit($arg)) $clubId = (int)$arg;
    elseif ($arg === '-h' || $arg === '--help') {
        echo "Uzycie: php cli/backup_club.php <clubId> [--gzip|--no-gzip]\n";
        echo "        php cli/backup_club.php --all  [--gzip|--no-gzip]\n";
        exit(0);
    } else {
        fwrite(STDERR, "Nieznany argument: $arg\n");
        exit(1);
    }
}

if (!$all && $clubId === null) {
    fwrite(STDERR, "Podaj <clubId> lub --all.\n");
    exit(1);
}

// --- backup directory -------------------------------------------------
$backupDir = ROOT_PATH . '/storage/backups';
if (!is_dir($backupDir) && !@mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    fwrite(STDERR, "Nie moge utworzyc $backupDir\n");
    exit(2);
}

// --- list of club-scoped tables (sync z AdminAuditController) ---------
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'], $config['port'], $config['dbname'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

// Auto-discover wszystkich tabel z kolumna club_id w obecnej bazie.
// Dzieki temu nie musimy synchronizowac listy z AdminAuditController.
$stmt = $pdo->prepare(
    "SELECT TABLE_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'club_id'
     ORDER BY TABLE_NAME"
);
$stmt->execute([$config['dbname']]);
$tables = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'TABLE_NAME');

if (empty($tables)) {
    fwrite(STDERR, "Brak tabel z kolumna club_id — nic do backupu.\n");
    exit(0);
}

echo "Znaleziono " . count($tables) . " tabel klubowych.\n";

// --- resolve target club(s) -------------------------------------------
$targets = [];
if ($all) {
    $rows = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $targets[] = ['id' => (int)$r['id'], 'name' => (string)$r['name']];
    }
} else {
    $stmt = $pdo->prepare("SELECT id, name FROM clubs WHERE id = ?");
    $stmt->execute([$clubId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        fwrite(STDERR, "Klub id=$clubId nie istnieje.\n");
        exit(3);
    }
    $targets[] = ['id' => (int)$row['id'], 'name' => (string)$row['name']];
}

// --- per-club backup -------------------------------------------------
$ts = date('Ymd_His');
$mysqldump = 'mysqldump';

// Wymagamy mysqldump w PATH — sprawdzmy zanim zaczniemy.
$check = shell_exec('which ' . escapeshellarg($mysqldump) . ' 2>/dev/null');
if (!is_string($check) || trim($check) === '') {
    fwrite(STDERR, "mysqldump nie jest dostepny w PATH. Zainstaluj mysql-client.\n");
    exit(4);
}

foreach ($targets as $club) {
    $cid   = $club['id'];
    $cname = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $club['name'] ?: ('club' . $cid));
    $base  = "club_{$cid}_{$cname}_{$ts}.sql";
    $out   = $backupDir . '/' . $base;

    echo "→ Backup klub id=$cid ({$club['name']}) → $base\n";

    // Bezpieczne hasło przez MYSQL_PWD env (nie ujawniamy w argv).
    $env = $_ENV;
    $env['MYSQL_PWD'] = $config['password'];

    // Otwieramy plik wynikowy raz, dopisujemy dump kazdej tabeli.
    $fh = fopen($out, 'w');
    if ($fh === false) {
        fwrite(STDERR, "Nie moge otworzyc $out do zapisu\n");
        exit(5);
    }
    fwrite($fh, "-- ClubDesk per-club backup\n");
    fwrite($fh, "-- club_id={$cid} name=" . str_replace("\n", ' ', $club['name']) . " generated_at=" . date('c') . "\n");
    fwrite($fh, "-- WAZNE: ten dump zawiera dane TYLKO klubu id={$cid}\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tables as $t) {
        $args = [
            '--no-tablespaces',
            '--skip-add-drop-table',
            '--skip-comments',
            '--single-transaction',
            '--default-character-set=utf8mb4',
            '--host=' . escapeshellarg($config['host']),
            '--port=' . (int)$config['port'],
            '--user=' . escapeshellarg($config['username']),
            '--where=' . escapeshellarg('club_id=' . $cid),
            escapeshellarg($config['dbname']),
            escapeshellarg($t),
        ];
        $cmd = $mysqldump . ' ' . implode(' ', $args);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes, null, $env);
        if (!is_resource($proc)) {
            fclose($fh);
            fwrite(STDERR, "Nie moge uruchomic mysqldump dla $t\n");
            exit(6);
        }
        fclose($pipes[0]);

        fwrite($fh, "-- table: $t (WHERE club_id=$cid)\n");
        while (!feof($pipes[1])) {
            $chunk = fread($pipes[1], 65536);
            if ($chunk === false) break;
            fwrite($fh, $chunk);
        }
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $rc = proc_close($proc);
        if ($rc !== 0) {
            // Tabela moze nie istniec na danej instancji — log i continue.
            fwrite($fh, "-- WARN: mysqldump $t rc=$rc stderr=" . trim((string)$stderr) . "\n");
            fwrite(STDERR, "  [warn] $t: rc=$rc\n");
        }
        fwrite($fh, "\n");
    }

    fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    if ($gzip) {
        $gz = $out . '.gz';
        $in  = fopen($out, 'rb');
        $gzh = gzopen($gz, 'wb9');
        if (!is_resource($in) || $gzh === false) {
            fwrite(STDERR, "  [warn] gzip nie powiodl sie, pozostawiam $out\n");
        } else {
            while (!feof($in)) {
                $chunk = fread($in, 65536);
                if ($chunk === false) break;
                gzwrite($gzh, $chunk);
            }
            fclose($in);
            gzclose($gzh);
            @unlink($out);
            echo "  ok: $gz (" . round(filesize($gz) / 1024, 1) . " KB)\n";
            continue;
        }
    }
    echo "  ok: $out (" . round(filesize($out) / 1024, 1) . " KB)\n";
}

echo "Done.\n";
