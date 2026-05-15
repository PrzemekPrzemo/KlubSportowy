<?php
// ============================================================
// cli/seed_sport_modules.php
// ------------------------------------------------------------
// Skanuje app/Sports/*/migrations/*.sql, wyciąga CREATE TABLE i
// auto-rejestruje wpisy w `sport_module_resources` aby generic
// SportModuleController mial pelna whiteliste.
//
// Bezpieczne re-runy: ON DUPLICATE KEY UPDATE label/icon.
// Sporty z dedykowanymi controllerami (Football, Basketball,
// Volleyball, Shooting, Rollerskating, Athletics) nadal mają
// własne URL-e w manifeście — generic /sport/<key>/<resource>
// współistnieje (inny prefix).
//
// Usage:
//   php cli/seed_sport_modules.php             # uruchom
//   php cli/seed_sport_modules.php --dry-run   # pokaż co byłoby zrobione
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

$dryRun = in_array('--dry-run', $argv, true);

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'], $config['port'], $config['dbname'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Sprawdz czy tabela istnieje (migracja 073 wykonana)
$exists = $pdo->query("SHOW TABLES LIKE 'sport_module_resources'")->fetchColumn();
if (!$exists) {
    fwrite(STDERR, "Tabela `sport_module_resources` nie istnieje. Uruchom najpierw migrację 073_sport_module_config.sql\n");
    exit(1);
}

// Załaduj manifesty aby zbudować mapę folder → sport_key
$folderToSport = [];
foreach (glob(ROOT_PATH . '/app/Sports/*/manifest.php') as $manifestPath) {
    try {
        $manifest = require $manifestPath;
    } catch (\Throwable) {
        continue;
    }
    if (!is_array($manifest) || empty($manifest['key'])) continue;
    $folder = basename(dirname($manifestPath));
    $folderToSport[$folder] = (string)$manifest['key'];
}

// Ikony defaultowe per resource (heurystyka)
$iconFor = function (string $resourceKey): string {
    $map = [
        'belts' => 'bi-award', 'grades' => 'bi-award',
        'weight_classes' => 'bi-speedometer2',
        'matches' => 'bi-flag', 'games' => 'bi-flag', 'rounds' => 'bi-arrow-repeat',
        'teams' => 'bi-people', 'players' => 'bi-person',
        'tournaments' => 'bi-trophy', 'results' => 'bi-trophy',
        'horses' => 'bi-emoji-smile',
        'wods' => 'bi-fire', 'prs' => 'bi-graph-up-arrow',
        'records' => 'bi-bar-chart',
        'attempts' => 'bi-list-check',
        'slopes' => 'bi-snow', 'runs' => 'bi-stopwatch',
        'rinks' => 'bi-circle', 'courts' => 'bi-grid-3x3',
        'scores' => 'bi-123',
        'bows' => 'bi-bullseye',
        'routines' => 'bi-music-note',
        'couples' => 'bi-people',
        'performances' => 'bi-camera-video',
        'member_grades' => 'bi-clock-history',
        'events' => 'bi-calendar-event',
        'stats' => 'bi-bar-chart',
        'leagues' => 'bi-table',
        'competitions' => 'bi-trophy',
        'personal_records' => 'bi-graph-up-arrow',
    ];
    return $map[$resourceKey] ?? 'bi-table';
};

// Labelka PL per resource
$labelFor = function (string $resourceKey): string {
    $map = [
        'belts' => 'Pasy / stopnie',
        'weight_classes' => 'Kategorie wagowe',
        'member_grades' => 'Stopnie zawodników',
        'matches' => 'Mecze',
        'games' => 'Gry',
        'teams' => 'Drużyny',
        'players' => 'Zawodnicy',
        'tournaments' => 'Turnieje',
        'results' => 'Wyniki',
        'horses' => 'Konie',
        'horse_health' => 'Zdrowie koni',
        'horse_training' => 'Trening koni',
        'wods' => 'WOD-y',
        'prs' => 'Rekordy życiowe (PR)',
        'personal_records' => 'Rekordy osobiste',
        'records' => 'Rekordy',
        'attempts' => 'Próby',
        'slopes' => 'Trasy / stoki',
        'runs' => 'Przejazdy',
        'rinks' => 'Lodowiska',
        'courts' => 'Korty',
        'scores' => 'Wyniki punktowe',
        'bows' => 'Łuki',
        'routines' => 'Układy choreograficzne',
        'couples' => 'Pary',
        'performances' => 'Występy',
        'events' => 'Zdarzenia',
        'stats' => 'Statystyki',
        'leagues' => 'Ligi',
        'competitions' => 'Zawody',
        'ratings' => 'Rankingi',
        'rounds' => 'Rundy',
        'disciplines' => 'Dyscypliny',
        'handicaps' => 'Handicapy',
        'assignments' => 'Przydziały',
        'team_members' => 'Członkowie drużyn',
        'match_stats' => 'Statystyki meczowe',
        'sets' => 'Sety',
    ];
    return $map[$resourceKey] ?? ucfirst(str_replace('_', ' ', $resourceKey));
};

// Niektóre sporty używają w migracjach legacy/innego prefixu — tu mapowanie.
// Sport key → dodatkowe prefixy (oprócz default sport_key + snake_case folder).
$extraPrefixesPerSport = [
    'kayaking'    => ['kayak'],     // legacy: kayak_boats, kayak_results
    'dance_sport' => ['dance'],     // DanceSport migracje uzywaja dance_*
];

// Lista prefixów per-sport (tabele pasujące do `<prefix>_<reszta>` należą do tego sportu)
$prefixesForSport = function (string $sportKey, string $folder) use ($extraPrefixesPerSport): array {
    // Konwertuj 'AlpineSki' → 'alpine_ski', 'TableTennis' → 'table_tennis'
    $snake = strtolower((string)preg_replace('/([a-z])([A-Z])/', '$1_$2', $folder));
    $prefixes = [$sportKey, $snake];
    if (isset($extraPrefixesPerSport[$sportKey])) {
        $prefixes = array_merge($prefixes, $extraPrefixesPerSport[$sportKey]);
    }
    return array_values(array_unique($prefixes));
};

// Helper: snake_case → CamelCase (belts → Belts, weight_classes → WeightClasses)
$camelize = function (string $snake): string {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $snake)));
};

// Czy sport ma dedykowany Controller dla danego resource (Belts → BeltsController.php)?
// Jeśli tak — generic CRUD oznaczamy is_active=0 (admin może włączyć ręcznie).
$hasDedicatedController = function (string $folder, string $resourceKey) use ($camelize): bool {
    $controllerPath = ROOT_PATH . '/app/Sports/' . $folder . '/Controllers/' . $camelize($resourceKey) . 'Controller.php';
    return file_exists($controllerPath);
};

$insertSql = "INSERT INTO sport_module_resources
    (sport_key, resource_key, resource_label, table_name, icon, sort_order, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE resource_label = VALUES(resource_label), icon = VALUES(icon)";
$stmt = $pdo->prepare($insertSql);

$inserted = 0;
$updated  = 0;
$skipped  = 0;

foreach ($folderToSport as $folder => $sportKey) {
    $migrationsDir = ROOT_PATH . '/app/Sports/' . $folder . '/migrations';
    if (!is_dir($migrationsDir)) continue;

    $prefixes = $prefixesForSport($sportKey, $folder);
    // Najdłuższe pierwsze (np. 'table_tennis' przed 'table')
    usort($prefixes, fn($a, $b) => strlen($b) - strlen($a));

    $tablesFound = [];
    foreach (glob($migrationsDir . '/*.sql') as $sqlFile) {
        $sql = (string)file_get_contents($sqlFile);
        if (!preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-z_][a-z0-9_]*)`?\s*\(/i', $sql, $m)) {
            continue;
        }
        foreach ($m[1] as $tableName) {
            $tablesFound[strtolower($tableName)] = true;
        }
    }

    foreach (array_keys($tablesFound) as $table) {
        $matchedPrefix = null;
        foreach ($prefixes as $p) {
            if (str_starts_with($table, $p . '_')) {
                $matchedPrefix = $p;
                break;
            }
        }
        if ($matchedPrefix === null) {
            // Tabela nie pasuje do prefixu sportu — pewnie wspólna
            $skipped++;
            continue;
        }

        $resourceKey = substr($table, strlen($matchedPrefix) + 1);
        // Niekiedy migracje dodają ALTER TABLE — wykluczamy obce / dziwne nazwy
        if ($resourceKey === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $resourceKey)) {
            $skipped++;
            continue;
        }

        $label = $labelFor($resourceKey);
        $icon  = $iconFor($resourceKey);
        $isActive = $hasDedicatedController($folder, $resourceKey) ? 0 : 1;

        if ($dryRun) {
            $status = $isActive ? 'active' : 'inactive (dedicated)';
            echo "  [DRY] {$sportKey}.{$resourceKey} → {$table}  ({$label}) [{$status}]\n";
            continue;
        }

        // Sprawdz czy istnieje (dla licznika)
        $check = $pdo->prepare("SELECT id FROM sport_module_resources WHERE sport_key = ? AND resource_key = ?");
        $check->execute([$sportKey, $resourceKey]);
        $exists = $check->fetchColumn();

        $stmt->execute([$sportKey, $resourceKey, $label, $table, $icon, 0, $isActive]);
        if ($exists) {
            $updated++;
            echo "  [UPDATE] {$sportKey}.{$resourceKey} → {$table}\n";
        } else {
            $inserted++;
            echo "  [INSERT] {$sportKey}.{$resourceKey} → {$table}  ({$label})\n";
        }
    }
}

echo "\n";
echo "Podsumowanie:\n";
echo "  Dodanych: {$inserted}\n";
echo "  Zaktualizowanych: {$updated}\n";
echo "  Pominiętych (poza prefixem): {$skipped}\n";

if ($dryRun) {
    echo "\n[DRY RUN] — żadne zmiany nie zapisane do DB.\n";
}
