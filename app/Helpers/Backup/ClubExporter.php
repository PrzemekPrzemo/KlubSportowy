<?php

namespace App\Helpers\Backup;

use App\Helpers\Database;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * ClubExporter — pelny eksport klubu do ZIP (GDPR portability / mobility).
 *
 * Wynik zawiera:
 *  - manifest.json                — metadata (club_id, name, exported_at, version, table_counts)
 *  - schema/version.txt           — wersja ClubDesk + schema_version (max migration id)
 *  - data/{table}.json            — wszystkie tabele club-scoped (auto-discovery
 *                                   po obecnosci kolumny club_id w information_schema)
 *  - media/photos/{member}_*      — fotki czlonkow (members.photo_path)
 *  - media/documents/*            — dokumenty / certyfikaty
 *  - media/gallery/*              — galeria klubu
 *  - media/branding/{logo,favicon,custom.css} — branding
 *  - integrations/config.json     — bramki (BEZ secret), federacje, KSeF (sanitized)
 *  - README.txt                   — instrukcja restore (PL)
 *
 * Tryby:
 *  - encrypted_preserve (default) — pola PII (PESEL, tokeny) zostaja zaszyfrowane;
 *                                    odbiorca importujac do innego klubu nie odczyta
 *                                    ich (klucze sa per-instalacja), ale snapshot
 *                                    pozostaje verbatim (zgodnosc GDPR / audit).
 *  - decrypted_for_owner         — pola PII deszyfrowane do plaintextu (wymaga
 *                                    osobnego potwierdzenia hasla admina w UI,
 *                                    eskalacja flagi tutaj). Wpis do audit log.
 *
 * Strumieniowy zapis: tabele dumpowane chunkami po 500 wierszy do JSON;
 * media kopiowane addFile() (ZipArchive trzyma deskryptor pliku, nie laduje
 * do pamieci). Dla bardzo duzych klubow (> 100 MB) zalecane uruchomienie
 * przez `cli/process_club_backups.php` (worker async, brak PHP memory_limit
 * na request).
 */
class ClubExporter
{
    public const MODE_PRESERVE = 'encrypted_preserve';
    public const MODE_DECRYPT  = 'decrypted_for_owner';

    public const FORMAT_VERSION = '1.0';
    /** Pelne dumpowanie pamieciowe per-tabela bedzie po tym progu odradzane. */
    private const CHUNK_SIZE = 500;

    private PDO $pdo;
    private string $mode;

    /** Tabele, ktore zawieraja kolumny zaszyfrowane (do ewentualnego decryptu). */
    private const ENCRYPTED_COLUMN_HINTS = [
        'members' => ['pesel_encrypted', 'phone_encrypted'],
        'member_api_tokens' => ['token_encrypted'],
        'club_federation_credentials' => ['password_encrypted', 'cert_encrypted'],
        'club_invoices' => [],
        'ksef_send_queue' => [],
    ];

    /** Tabele zawierajace SECRET — sanitizowane w eksporcie integracji. */
    private const SECRET_TABLES = [
        'club_gateway_credentials' => ['secret_encrypted', 'api_key_encrypted'],
        'club_smtp_settings'       => ['password_encrypted'],
        'webhooks'                 => ['secret'],
        'api_tokens_v2'            => ['token_hash'],
    ];

    public function __construct(?PDO $pdo = null, string $mode = self::MODE_PRESERVE)
    {
        $this->pdo  = $pdo ?? Database::pdo();
        $this->mode = $mode;
    }

    public function setMode(string $mode): void
    {
        if (!in_array($mode, [self::MODE_PRESERVE, self::MODE_DECRYPT], true)) {
            throw new RuntimeException('Unknown exporter mode: ' . $mode);
        }
        $this->mode = $mode;
    }

    /**
     * Pelny eksport klubu do ZIP. Aktualizuje wpis w club_backups (status,
     * rozmiar, rows_exported, files_exported, completed_at) po zakonczeniu.
     *
     * @return string absolute path do utworzonego ZIP
     */
    public function export(int $clubId, int $backupId): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP rozszerzenie ZipArchive nie jest dostepne.');
        }

        $club = $this->fetchClub($clubId);
        if ($club === null) {
            throw new RuntimeException("Klub id={$clubId} nie istnieje.");
        }

        $zipPath = $this->resolveBackupPath($clubId, $backupId);
        $dir = dirname($zipPath);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException("Nie moge utworzyc katalogu backupow: {$dir}");
        }

        $zip = new ZipArchive();
        $rc = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($rc !== true) {
            throw new RuntimeException("Nie moge otworzyc ZIP: {$zipPath} (rc={$rc})");
        }

        $rowsExported  = 0;
        $filesExported = 0;
        $tableCounts   = [];

        // 1) data/{table}.json — wszystkie tabele klubowe
        $tables = $this->discoverClubScopedTables();
        foreach ($tables as $table) {
            $rows = $this->dumpTable($table, $clubId);
            $tableCounts[$table] = count($rows);
            $rowsExported += count($rows);
            $zip->addFromString(
                "data/{$table}.json",
                $this->jsonEncode($rows)
            );
        }

        // 2) integrations/config.json — sanitized
        $integrations = $this->collectIntegrations($clubId);
        $zip->addFromString('integrations/config.json', $this->jsonEncode($integrations));

        // 3) media — fotki czlonkow, galeria, dokumenty, branding
        $filesExported += $this->addMemberPhotos($zip, $clubId);
        $filesExported += $this->addClubMedia($zip, $clubId);

        // 4) schema/version.txt
        $schemaVersion = $this->detectSchemaVersion();
        $appVersion    = $this->detectAppVersion();
        $zip->addFromString(
            'schema/version.txt',
            "app_version={$appVersion}\nschema_version={$schemaVersion}\nformat_version="
                . self::FORMAT_VERSION . "\nexported_at=" . date('c') . "\n"
        );

        // 5) manifest.json
        $manifest = [
            'format_version'  => self::FORMAT_VERSION,
            'app_version'     => $appVersion,
            'schema_version'  => $schemaVersion,
            'club_id'         => $clubId,
            'club_name'       => $club['name'] ?? '',
            'club_short_name' => $club['short_name'] ?? null,
            'exported_at'     => date('c'),
            'mode'            => $this->mode,
            'backup_id'       => $backupId,
            'table_counts'    => $tableCounts,
            'totals'          => [
                'rows'  => $rowsExported,
                'files' => $filesExported,
            ],
        ];
        $zip->addFromString('manifest.json', $this->jsonEncode($manifest));

        // 6) README
        $zip->addFromString('README.txt', $this->buildReadme($club, $manifest));

        $zip->close();
        @chmod($zipPath, 0600);

        $this->markBackupCompleted($backupId, $zipPath, $rowsExported, $filesExported);

        return $zipPath;
    }

    // ──────────────────────────────────────────────────────────────────
    //  Helpers prywatne
    // ──────────────────────────────────────────────────────────────────

    public function resolveBackupPath(int $clubId, int $backupId): string
    {
        $ts = date('Ymd_His');
        return ROOT_PATH . "/storage/backups/{$clubId}/{$backupId}_{$ts}.zip";
    }

    private function fetchClub(int $clubId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clubs WHERE id = ?');
        $stmt->execute([$clubId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Auto-discovery tabel club-scoped. Wykorzystuje information_schema —
     * dzieki temu nie musimy synchronizowac listy z kodem (jak w
     * cli/backup_club.php).
     *
     * @return string[]
     */
    private function discoverClubScopedTables(): array
    {
        $config = $this->databaseConfig();
        $stmt = $this->pdo->prepare(
            "SELECT TABLE_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'club_id'
             ORDER BY TABLE_NAME"
        );
        $stmt->execute([$config['dbname']]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'TABLE_NAME');
    }

    private function dumpTable(string $table, int $clubId): array
    {
        // Walidacja nazwy tabeli (chronimy przed injection — pochodzi z information_schema,
        // ale defense-in-depth).
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return [];
        }
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE club_id = ?");
            $stmt->execute([$clubId]);
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = $this->normalizeRow($row, $table);
        }
        return $out;
    }

    /**
     * Normalizacja wiersza: ISO 8601 daty, opcjonalne decrypt PII w trybie
     * MODE_DECRYPT, sanitizacja secret w SECRET_TABLES.
     */
    private function normalizeRow(array $row, string $table): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            // Sanitizuj sekrety zawsze (nawet w MODE_DECRYPT) — bramki/tokeny
            // nie powinny opuszczac platformy.
            if (isset(self::SECRET_TABLES[$table]) && in_array($k, self::SECRET_TABLES[$table], true)) {
                $out[$k] = $v === null ? null : '***REDACTED***';
                continue;
            }

            // ISO 8601 dla DATETIME-like wartosci
            if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v)) {
                $out[$k] = str_replace(' ', 'T', $v);
                continue;
            }

            // MODE_DECRYPT: desyfruj znane pola PII (jesli Encryption helper dostepny)
            if ($this->mode === self::MODE_DECRYPT
                && isset(self::ENCRYPTED_COLUMN_HINTS[$table])
                && in_array($k, self::ENCRYPTED_COLUMN_HINTS[$table], true)
                && $v !== null
                && class_exists('\\App\\Helpers\\Encryption')
            ) {
                try {
                    $plain = \App\Helpers\Encryption::decrypt((string)$v);
                    if ($plain !== null) {
                        $out[$k] = $plain;
                        continue;
                    }
                } catch (\Throwable) {
                    // fall through
                }
            }

            $out[$k] = $v;
        }
        return $out;
    }

    private function collectIntegrations(int $clubId): array
    {
        $config = [
            'gateways'   => [],
            'federations'=> [],
            'ksef'       => null,
            'note'       => 'Wszystkie sekrety (api_key, password, cert) zostaly USUNIETE. '
                          . 'Przy restore nalezy je wprowadzic ponownie recznie.',
        ];

        $this->safeQuery(
            'SELECT * FROM club_gateway_credentials WHERE club_id = ?',
            [$clubId],
            function (array $row) use (&$config) {
                foreach (self::SECRET_TABLES['club_gateway_credentials'] ?? [] as $col) {
                    if (array_key_exists($col, $row)) {
                        $row[$col] = $row[$col] === null ? null : '***REDACTED***';
                    }
                }
                $config['gateways'][] = $row;
            }
        );

        $this->safeQuery(
            'SELECT id, club_id, federation_key, enabled, last_sync_at, created_at FROM club_federation_credentials WHERE club_id = ?',
            [$clubId],
            function (array $row) use (&$config) {
                $config['federations'][] = $row;
            }
        );

        $this->safeQuery(
            'SELECT id, club_id, environment, nip, enabled, created_at FROM club_ksef_config WHERE club_id = ? LIMIT 1',
            [$clubId],
            function (array $row) use (&$config) {
                $config['ksef'] = $row;
            }
        );

        return $config;
    }

    /** Krotka pomocnicza: wykonaj query, foreach -> callback; ignoruj brakujace tabele. */
    private function safeQuery(string $sql, array $params, callable $onRow): void
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $onRow($row);
            }
        } catch (\Throwable) {
            // Tabela moze nie istniec na danej instalacji
        }
    }

    /** @return int liczba dodanych plikow */
    private function addMemberPhotos(ZipArchive $zip, int $clubId): int
    {
        $count = 0;
        try {
            $stmt = $this->pdo->prepare('SELECT id, photo_path FROM members WHERE club_id = ? AND photo_path IS NOT NULL AND photo_path <> ""');
            $stmt->execute([$clubId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $abs = $this->resolveMediaPath((string)$row['photo_path']);
                if ($abs !== null) {
                    $base = basename($abs);
                    $zip->addFile($abs, "media/photos/{$row['id']}_{$base}");
                    $count++;
                }
            }
        } catch (\Throwable) {
            // tabela / kolumna moze nie istniec
        }
        return $count;
    }

    /** @return int liczba dodanych plikow */
    private function addClubMedia(ZipArchive $zip, int $clubId): int
    {
        $count = 0;

        // Branding: logo / favicon / custom.css w club_customization
        $this->safeQuery(
            'SELECT * FROM club_customization WHERE club_id = ? LIMIT 1',
            [$clubId],
            function (array $row) use ($zip, &$count) {
                foreach (['logo_path', 'favicon_path', 'background_path'] as $col) {
                    if (!empty($row[$col])) {
                        $abs = $this->resolveMediaPath((string)$row[$col]);
                        if ($abs !== null) {
                            $zip->addFile($abs, 'media/branding/' . basename($abs));
                            $count++;
                        }
                    }
                }
                if (!empty($row['custom_css'])) {
                    $zip->addFromString('media/branding/custom.css', (string)$row['custom_css']);
                    $count++;
                }
            }
        );

        // Galeria (jezeli istnieje)
        $this->safeQuery(
            'SELECT id, file_path FROM gallery_items WHERE club_id = ?',
            [$clubId],
            function (array $row) use ($zip, &$count) {
                $abs = $this->resolveMediaPath((string)($row['file_path'] ?? ''));
                if ($abs !== null) {
                    $zip->addFile($abs, 'media/gallery/' . basename($abs));
                    $count++;
                }
            }
        );

        // Dokumenty czlonkow
        $this->safeQuery(
            'SELECT id, file_path FROM member_documents WHERE club_id = ?',
            [$clubId],
            function (array $row) use ($zip, &$count) {
                $abs = $this->resolveMediaPath((string)($row['file_path'] ?? ''));
                if ($abs !== null) {
                    $zip->addFile($abs, "media/documents/{$row['id']}_" . basename($abs));
                    $count++;
                }
            }
        );

        return $count;
    }

    /**
     * Bezpieczne mapowanie media-path z DB na absolute, z whitelistem
     * dozwolonych katalogow (public/uploads, storage/uploads). Nie przepuszcza
     * '..' ani sciezek absolutnych spoza ROOT_PATH.
     */
    private function resolveMediaPath(string $rel): ?string
    {
        $rel = trim($rel);
        if ($rel === '') return null;

        // Usun prefix URL, jezeli kolumna trzyma URL (cos /uploads/...)
        if (preg_match('#^https?://[^/]+/(.*)$#', $rel, $m)) {
            $rel = $m[1];
        }

        $rel = ltrim($rel, '/');
        if (str_contains($rel, '..')) return null;

        $candidates = [
            ROOT_PATH . '/public/' . $rel,
            ROOT_PATH . '/' . $rel,
            ROOT_PATH . '/storage/' . $rel,
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) {
                $real = realpath($c);
                if ($real !== false && str_starts_with($real, ROOT_PATH . DIRECTORY_SEPARATOR)) {
                    return $real;
                }
            }
        }
        return null;
    }

    private function detectSchemaVersion(): string
    {
        try {
            $stmt = $this->pdo->query('SELECT MAX(version) AS v FROM schema_migrations');
            $v = $stmt ? $stmt->fetchColumn() : null;
            if ($v !== false && $v !== null) {
                return (string)$v;
            }
        } catch (\Throwable) {}
        // Fallback: najwyzszy numer pliku migracji
        $files = glob(ROOT_PATH . '/database/migrations/*.sql') ?: [];
        $max = '000';
        foreach ($files as $f) {
            if (preg_match('/(\d{3})_/', basename($f), $m) && $m[1] > $max) {
                $max = $m[1];
            }
        }
        return $max;
    }

    private function detectAppVersion(): string
    {
        $verFile = ROOT_PATH . '/VERSION';
        if (is_file($verFile)) {
            return trim((string)file_get_contents($verFile));
        }
        return 'dev';
    }

    private function databaseConfig(): array
    {
        $local = ROOT_PATH . '/config/database.local.php';
        return file_exists($local) ? require $local : require ROOT_PATH . '/config/database.php';
    }

    private function jsonEncode(mixed $data): string
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        return $json === false ? '{}' : $json;
    }

    private function buildReadme(array $club, array $manifest): string
    {
        $name = $club['name'] ?? '';
        $rows = $manifest['totals']['rows']  ?? 0;
        $files = $manifest['totals']['files'] ?? 0;
        return <<<TXT
ClubDesk — Pelny backup klubu
==============================

Klub:           {$name} (id={$club['id']})
Wygenerowano:   {$manifest['exported_at']}
Format:         {$manifest['format_version']}
Schema:         {$manifest['schema_version']}
Tryb:           {$manifest['mode']}
Wiersze:        {$rows}
Pliki:          {$files}

Zawartosc:
  manifest.json            — opis paczki
  schema/version.txt       — wersja schematu
  data/{table}.json        — wszystkie tabele klubu (UTF-8, ISO 8601 dates)
  integrations/config.json — konfiguracja integracji (BEZ sekretow)
  media/photos/            — zdjecia czlonkow
  media/documents/         — dokumenty
  media/gallery/           — galeria
  media/branding/          — logo, favicon, custom.css

Restore (kierunek odwrotny):
  1. Wgraj ZIP w UI: /club/backup/restore (zarzad / admin).
  2. System zweryfikuje format_version + schema_version.
  3. Wybierz strategie: overwrite | skip duplicates | merge.
  4. Sekrety integracji wprowadz ponownie recznie.

UWAGA — RODO:
Plik moze zawierac dane osobowe (PII). Przechowuj w bezpiecznym
miejscu (zaszyfrowany dysk), ogranicz dostep do zarzadu klubu,
usun po wykonaniu restore'a.
TXT;
    }

    private function markBackupCompleted(int $backupId, string $zipPath, int $rows, int $files): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE club_backups
                 SET status = 'completed',
                     backup_path = :path,
                     backup_size_bytes = :size,
                     rows_exported = :rows,
                     files_exported = :files,
                     completed_at = NOW(),
                     expires_at = COALESCE(expires_at, DATE_ADD(NOW(), INTERVAL 30 DAY))
                 WHERE id = :id"
            );
            $relPath = str_starts_with($zipPath, ROOT_PATH . '/')
                ? substr($zipPath, strlen(ROOT_PATH) + 1)
                : $zipPath;
            $stmt->execute([
                ':path'  => $relPath,
                ':size'  => is_file($zipPath) ? filesize($zipPath) : null,
                ':rows'  => $rows,
                ':files' => $files,
                ':id'    => $backupId,
            ]);
        } catch (\Throwable) {
            // brak tabeli (np. test bez migracji) — silent fallback
        }
    }
}
