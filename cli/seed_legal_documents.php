<?php
// ============================================================
// cli/seed_legal_documents.php
// Wczytuje 6 dokumentów prawnych z database/seeds/legal/*.md
// i publikuje je jako bieżącą wersję 1.0 dla locale='pl'.
//
// Bezpieczne do wielokrotnego uruchamiania: jeśli wersja 1.0
// dokumentu danego typu już istnieje — pomija ją; w przeciwnym
// wypadku korzysta z LegalDocumentModel::publishNewVersion(),
// który ustawia is_current=1 i zeruje wcześniejsze wersje.
//
// Uruchomienie:  php cli/seed_legal_documents.php
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

use App\Helpers\Database;
use App\Models\LegalDocumentModel;

// Reuse normal DB config (Database::pdo() reads config/database.local.php → database.php).
$pdo = Database::pdo();

// Run the 082 migration first (CREATE TABLE IF NOT EXISTS — idempotent).
$migrationSql = file_get_contents(ROOT_PATH . '/database/migrations/082_legal_documents.sql');
if ($migrationSql === false) {
    fwrite(STDERR, "Nie można odczytać database/migrations/082_legal_documents.sql\n");
    exit(1);
}
echo "→ Uruchamiam migrację 082 (legal_documents, legal_acceptances)...\n";
$pdo->exec($migrationSql);
echo "  OK\n";

$effectiveFrom = date('Y-m-01'); // pierwszy dzień bieżącego miesiąca

$docs = [
    ['tos',           '01_tos.md',           'Regulamin świadczenia usług drogą elektroniczną ClubDesk'],
    ['privacy',       '02_privacy.md',       'Polityka prywatności ClubDesk'],
    ['cookies',       '03_cookies.md',       'Polityka cookies ClubDesk'],
    ['dpa',           '04_dpa.md',           'Umowa powierzenia przetwarzania danych osobowych (DPA)'],
    ['sla',           '05_sla.md',           'Service Level Agreement (SLA) ClubDesk'],
    ['member_clause', '06_member_clause.md', 'Klauzula informacyjna RODO dla Członka Klubu'],
];

$model = new LegalDocumentModel();
$inserted = 0;
$skipped  = 0;

foreach ($docs as [$type, $file, $title]) {
    $path = ROOT_PATH . '/database/seeds/legal/' . $file;
    if (!file_exists($path)) {
        fwrite(STDERR, "  ! Brak pliku $path — pomijam $type\n");
        continue;
    }

    $existing = $model->byTypeAndVersion($type, '1.0', 'pl');
    if ($existing) {
        echo "  · {$type} v1.0 już istnieje (id={$existing['id']}) — pomijam.\n";
        $skipped++;
        continue;
    }

    $body = file_get_contents($path) ?: '';
    $id = $model->publishNewVersion([
        'doc_type'       => $type,
        'locale'         => 'pl',
        'version'        => '1.0',
        'effective_from' => $effectiveFrom,
        'title'          => $title,
        'body_md'        => $body,
    ]);
    echo "  + {$type} v1.0 (id={$id}, " . strlen($body) . " B) – ustawiono is_current=1.\n";
    $inserted++;
}

echo "Gotowe. Wstawiono: $inserted. Pominięto: $skipped.\n";
