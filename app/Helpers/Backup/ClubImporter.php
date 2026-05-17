<?php

namespace App\Helpers\Backup;

use App\Helpers\Database;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * ClubImporter — restore z ZIP wygenerowanego przez ClubExporter.
 *
 * Strategia:
 *   - walidacja manifest (format_version, schema_version) PRZED jakimkolwiek INSERT-em
 *   - importuje do EXISTING klubu (overwrite albo merge); NEW klub powinien byc
 *     utworzony przez controller PRZED wywolaniem importu (UI flow)
 *   - po dla bezpieczenstwa: usuwa kolumne `id` z importowanych wierszy zeby
 *     uniknac kolizji PK (AUTO_INCREMENT nada nowe)
 *   - klucze obce ramach klubu wymagaja mapowania (ID member ze starego klubu
 *     != nowy member.id) — bazowa implementacja zapisuje `import_id_map` w
 *     pamieci dla najczestrzych tabel (members, trainings, tournaments). Pelne
 *     remapowanie wszystkich FK jest poza scope tego MVP (TODO w issue).
 *
 * Bezpieczenstwo:
 *   - sekrety NIE sa przywracane (w eksporcie REDACTED) — admin musi je wpisac
 *   - klucze szyfrujace SA per-instalacja; pola encrypted z innego klubu beda
 *     nieczytelne (dlatego eksport ma tryb decrypted_for_owner)
 */
class ClubImporter
{
    private PDO $pdo;
    /** @var array<string,array<int,int>> mapping table => [old_id => new_id] */
    private array $idMap = [];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * Walidacja paczki PRZED importem. Sprawdza:
     *  - czy plik jest poprawnym ZIP
     *  - obecnosc manifest.json
     *  - format_version (1.x supported)
     *  - schema_version vs aktualny (warning gdy nizszy o > 5 migracji)
     *  - liczbe tabel i wierszy (do podgladu)
     *
     * @return array{valid:bool, errors:string[], warnings:string[], manifest:?array}
     */
    public function validate(string $zipPath): array
    {
        $errors = [];
        $warnings = [];
        $manifest = null;

        if (!is_file($zipPath)) {
            return ['valid' => false, 'errors' => ['Plik nie istnieje: ' . $zipPath], 'warnings' => [], 'manifest' => null];
        }
        if (!class_exists('ZipArchive')) {
            return ['valid' => false, 'errors' => ['Brak PHP rozszerzenia ZipArchive.'], 'warnings' => [], 'manifest' => null];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['valid' => false, 'errors' => ['Plik nie jest poprawnym ZIP.'], 'warnings' => [], 'manifest' => null];
        }

        $raw = $zip->getFromName('manifest.json');
        if ($raw === false) {
            $zip->close();
            return ['valid' => false, 'errors' => ['Brak manifest.json — nie jest to backup ClubDesk.'], 'warnings' => [], 'manifest' => null];
        }

        $manifest = json_decode($raw, true);
        if (!is_array($manifest)) {
            $zip->close();
            return ['valid' => false, 'errors' => ['manifest.json jest uszkodzony (zly JSON).'], 'warnings' => [], 'manifest' => null];
        }

        $fmt = (string)($manifest['format_version'] ?? '');
        if (!str_starts_with($fmt, '1.')) {
            $errors[] = "Nieobslugiwany format_version: {$fmt} (oczekiwano 1.x).";
        }

        $importSchema = (string)($manifest['schema_version'] ?? '000');
        $currentSchema = $this->detectCurrentSchemaVersion();
        if ((int)$importSchema > (int)$currentSchema) {
            $errors[] = "Backup ma nowszy schema_version ({$importSchema}) niz aktualny ({$currentSchema}). "
                . 'Zaktualizuj aplikacje przed importem.';
        } elseif ((int)$currentSchema - (int)$importSchema > 5) {
            $warnings[] = "Backup jest stary (schema {$importSchema} vs {$currentSchema}). "
                . 'Niektore tabele moga miec nowe kolumny — zostana wypelnione domyslnie.';
        }

        if (empty($manifest['table_counts']) || !is_array($manifest['table_counts'])) {
            $warnings[] = 'Manifest nie zawiera table_counts — paczka moze byc niekompletna.';
        }

        $zip->close();

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
            'manifest' => $manifest,
        ];
    }

    /**
     * Import ZIP do EXISTING klubu.
     *
     * @param array{overwrite?:bool, restore_media?:bool, dry_run?:bool} $options
     * @return array{success:bool, rows_imported:int, files_imported:int, errors:string[], warnings:string[]}
     */
    public function import(string $zipPath, int $targetClubId, array $options = []): array
    {
        $overwrite    = (bool)($options['overwrite']    ?? false);
        $restoreMedia = (bool)($options['restore_media'] ?? true);
        $dryRun       = (bool)($options['dry_run']      ?? false);

        $validation = $this->validate($zipPath);
        if (!$validation['valid']) {
            return [
                'success'        => false,
                'rows_imported'  => 0,
                'files_imported' => 0,
                'errors'         => $validation['errors'],
                'warnings'       => $validation['warnings'],
            ];
        }

        $errors   = [];
        $warnings = $validation['warnings'];
        $rowCount = 0;
        $fileCount = 0;
        $this->idMap = [];

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'rows_imported' => 0, 'files_imported' => 0,
                    'errors' => ['Nie moge otworzyc ZIP'], 'warnings' => $warnings];
        }

        $this->pdo->beginTransaction();
        try {
            // Tabele do importu — z information_schema
            $tables = $this->discoverClubScopedTables();

            if ($overwrite) {
                // Twardy reset: usun wszystkie wiersze z club_id = $targetClubId
                // Robimy w odwrotnej kolejnosci dla FK (best-effort).
                foreach (array_reverse($tables) as $t) {
                    try {
                        $stmt = $this->pdo->prepare("DELETE FROM `{$t}` WHERE club_id = ?");
                        $stmt->execute([$targetClubId]);
                    } catch (\Throwable $e) {
                        $warnings[] = "DELETE z {$t} nie powiodl sie: " . $e->getMessage();
                    }
                }
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false) continue;

                if (str_starts_with($name, 'data/') && str_ends_with($name, '.json')) {
                    $table = substr($name, 5, -5);
                    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) continue;
                    if (!in_array($table, $tables, true)) {
                        $warnings[] = "Tabela {$table} nie istnieje w obecnym schemacie — pominieto.";
                        continue;
                    }
                    $payload = json_decode((string)$zip->getFromIndex($i), true);
                    if (!is_array($payload)) continue;

                    $imported = $dryRun
                        ? count($payload)
                        : $this->importTableRows($table, $payload, $targetClubId, $overwrite);
                    $rowCount += $imported;
                } elseif ($restoreMedia && str_starts_with($name, 'media/')) {
                    if ($dryRun) { $fileCount++; continue; }
                    $extracted = $this->extractMediaFile($zip, $i, $name, $targetClubId);
                    if ($extracted) $fileCount++;
                }
            }

            if ($dryRun) {
                $this->pdo->rollBack();
            } else {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $errors[] = 'Import przerwany: ' . $e->getMessage();
            $zip->close();
            return ['success' => false, 'rows_imported' => 0, 'files_imported' => 0,
                    'errors' => $errors, 'warnings' => $warnings];
        }

        $zip->close();

        return [
            'success'        => empty($errors),
            'rows_imported'  => $rowCount,
            'files_imported' => $fileCount,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function importTableRows(string $table, array $rows, int $clubId, bool $overwrite): int
    {
        if (empty($rows)) return 0;

        // Kolumny obecne w docelowej tabeli
        $cols = $this->tableColumns($table);
        if (empty($cols)) return 0;

        $imported = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            // Wymus club_id docelowy (chroni przed cross-tenant smuggling)
            $row['club_id'] = $clubId;

            // Drop kolumn nieistniejacych + zapisz old_id do mappingu
            $oldId = isset($row['id']) ? (int)$row['id'] : null;
            unset($row['id']); // AUTO_INCREMENT nada nowe ID
            $insert = [];
            foreach ($row as $k => $v) {
                if (in_array($k, $cols, true)) {
                    $insert[$k] = $v;
                }
            }
            if (empty($insert)) continue;

            $colsSql = '`' . implode('`,`', array_keys($insert)) . '`';
            $params  = ':' . implode(', :', array_keys($insert));
            $sql     = "INSERT INTO `{$table}` ({$colsSql}) VALUES ({$params})";

            try {
                $stmt = $this->pdo->prepare($sql);
                $bind = [];
                foreach ($insert as $k => $v) {
                    $bind[':' . $k] = is_array($v) ? json_encode($v) : $v;
                }
                $stmt->execute($bind);
                $newId = (int)$this->pdo->lastInsertId();
                if ($oldId !== null && $newId > 0) {
                    $this->idMap[$table][$oldId] = $newId;
                }
                $imported++;
            } catch (\Throwable $e) {
                // Skip wierszy, ktore np. naruszaja UNIQUE w trybie merge.
                continue;
            }
        }
        return $imported;
    }

    private function extractMediaFile(ZipArchive $zip, int $index, string $name, int $clubId): bool
    {
        $rel  = ltrim(substr($name, strlen('media/')), '/');
        if ($rel === '' || str_contains($rel, '..')) return false;

        $destDir = ROOT_PATH . "/public/uploads/club_{$clubId}/restored";
        if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            return false;
        }
        $safe = preg_replace('/[^A-Za-z0-9._\/-]/', '_', $rel);
        $abs  = $destDir . '/' . $safe;
        $sub  = dirname($abs);
        if (!is_dir($sub)) @mkdir($sub, 0775, true);

        $data = $zip->getFromIndex($index);
        if ($data === false) return false;
        return file_put_contents($abs, $data) !== false;
    }

    private function tableColumns(string $table): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $stmt->execute([$table]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
        } catch (\Throwable) {
            return [];
        }
    }

    private function discoverClubScopedTables(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT TABLE_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'club_id'
                 ORDER BY TABLE_NAME"
            );
            return $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'TABLE_NAME') : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function detectCurrentSchemaVersion(): string
    {
        try {
            $stmt = $this->pdo->query('SELECT MAX(version) FROM schema_migrations');
            $v = $stmt ? $stmt->fetchColumn() : null;
            if ($v !== false && $v !== null) return (string)$v;
        } catch (\Throwable) {}
        $files = glob(ROOT_PATH . '/database/migrations/*.sql') ?: [];
        $max = '000';
        foreach ($files as $f) {
            if (preg_match('/(\d{3})_/', basename($f), $m) && $m[1] > $max) $max = $m[1];
        }
        return $max;
    }

    public function idMap(): array
    {
        return $this->idMap;
    }
}
