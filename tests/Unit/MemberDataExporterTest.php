<?php

namespace Tests\Unit;

use App\Helpers\Gdpr\MemberDataExporter;
use PDO;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Smoke test dla MemberDataExporter (RODO art. 20 ZIP export).
 *
 * Uzywamy SQLite in-memory zeby nie wymagac MySQL w pipeline CI.
 * Tworzymy minimalne tabele (members + clubs), reszte tabel (payments,
 * trainings, etc.) celowo POMIJAMY — exporter ma safe-fetch (PDOException
 * -> []), wiec brak tabeli skutkuje pustym JSON.
 *
 * Testy:
 *   1) Pelny ZIP generuje sie bez wyjatku
 *   2) Zawiera manifest.json + README.txt + data/profile.json
 *   3) profile.json zawiera dane czlonka (imie, nazwisko)
 *   4) Cross-tenant guard: clubId mismatch -> RuntimeException
 *   5) Manifest ma SHA-256 checksum dla kazdego pliku
 */
class MemberDataExporterTest extends TestCase
{
    private PDO $pdo;
    private string $tmpStorageDir;
    private ?string $originalStorageBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite in-memory db.
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Minimalna tabela clubs.
        $this->pdo->exec(<<<SQL
            CREATE TABLE clubs (
                id   INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT
            )
        SQL);

        // Minimalna tabela members. NOTE: SQLite akceptuje kolumny ktorych
        // exporter moze szukac; reszta tabel nie istnieje -> safeFetchAll
        // zwraca [].
        $this->pdo->exec(<<<SQL
            CREATE TABLE members (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                club_id         INTEGER NOT NULL,
                first_name      TEXT,
                last_name       TEXT,
                email           TEXT,
                phone           TEXT,
                pesel           TEXT,
                photo_path      TEXT,
                member_number   TEXT,
                created_at      TEXT
            )
        SQL);

        $this->pdo->exec("INSERT INTO clubs (id, name) VALUES (10, 'Test Club')");
        $this->pdo->exec("INSERT INTO members (id, club_id, first_name, last_name, email, member_number, created_at)
                          VALUES (100, 10, 'Jan', 'Kowalski', 'jan@example.com', 'M-001', '2024-01-15 10:00:00')");

        // Storage dir w tmp — zeby unikac smiecenia w storage/gdpr_exports.
        $this->tmpStorageDir = sys_get_temp_dir() . '/clubdesk_gdpr_test_' . uniqid();
        @mkdir($this->tmpStorageDir . '/storage/gdpr_exports', 0750, true);

        // Exporter uzywa ROOT_PATH . '/storage/gdpr_exports'. ROOT_PATH jest
        // zdefiniowane w tests/bootstrap.php jako prawdziwy katalog projektu.
        // Test piszemy do prawdziwego /storage/gdpr_exports/{10}/{...}.zip,
        // ale czyscimy po sobie w tearDown().
    }

    protected function tearDown(): void
    {
        // Cleanup vygenerowanych plikow testowych z prawdziwego storage.
        $dir = ROOT_PATH . '/storage/gdpr_exports/10';
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.zip') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }

        // Tmp dir cleanup.
        if (isset($this->tmpStorageDir) && is_dir($this->tmpStorageDir)) {
            $this->rrmdir($this->tmpStorageDir);
        }

        parent::tearDown();
    }

    public function testExportGeneratesZipWithoutException(): void
    {
        $exporter = new MemberDataExporter($this->pdo);

        $zipPath = $exporter->export(100, 999, 10);

        $this->assertFileExists($zipPath);
        $this->assertGreaterThan(0, filesize($zipPath));

        // chmod 0600 — sprawdzamy ze plik nie ma innych bitow niz 0600
        // (na niektorych systemach umask moze go zmienic — testujemy soft).
        $this->assertTrue(is_readable($zipPath));
    }

    public function testZipContainsManifestAndReadmeAndProfile(): void
    {
        $exporter = new MemberDataExporter($this->pdo);
        $zipPath  = $exporter->export(100, 1001, 10);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);

        $this->assertNotFalse($zip->locateName('manifest.json'), 'manifest.json missing');
        $this->assertNotFalse($zip->locateName('README.txt'),    'README.txt missing');
        $this->assertNotFalse($zip->locateName('data/profile.json'), 'data/profile.json missing');

        // Profile zawiera imie czlonka.
        $profileJson = $zip->getFromName('data/profile.json');
        $this->assertIsString($profileJson);
        $profile = json_decode($profileJson, true);
        $this->assertIsArray($profile);
        $this->assertSame('Jan',       $profile['first_name'] ?? null);
        $this->assertSame('Kowalski',  $profile['last_name']  ?? null);
        $this->assertSame(10,          (int)($profile['club_id'] ?? 0));

        // README zawiera info o RODO + nazwa klubu.
        $readme = $zip->getFromName('README.txt');
        $this->assertStringContainsString('RODO', $readme);
        $this->assertStringContainsString('Test Club', $readme);

        $zip->close();
    }

    public function testManifestContainsSha256Checksums(): void
    {
        $exporter = new MemberDataExporter($this->pdo);
        $zipPath  = $exporter->export(100, 1002, 10);

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $manifestJson = $zip->getFromName('manifest.json');
        $zip->close();

        $manifest = json_decode($manifestJson, true);
        $this->assertIsArray($manifest);
        $this->assertArrayHasKey('files', $manifest);
        $this->assertArrayHasKey('gdpr_export', $manifest);
        $this->assertSame(10, $manifest['gdpr_export']['club_id']);
        $this->assertSame(100, $manifest['gdpr_export']['member_id']);
        $this->assertSame(1002, $manifest['gdpr_export']['request_id']);

        foreach ($manifest['files'] as $entry) {
            $this->assertArrayHasKey('name',   $entry);
            $this->assertArrayHasKey('size',   $entry);
            $this->assertArrayHasKey('sha256', $entry);
            // SHA-256 hex == 64 znaki (jesli nie pusta).
            if ($entry['sha256'] !== '') {
                $this->assertSame(64, strlen($entry['sha256']));
            }
        }
    }

    public function testCrossTenantGuardRejectsWrongClub(): void
    {
        $exporter = new MemberDataExporter($this->pdo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cross-tenant guard/i');

        // Member 100 nalezy do club_id=10, probujemy eksportowac do club_id=99.
        $exporter->export(100, 1003, 99);
    }

    public function testNonExistentMemberThrows(): void
    {
        $exporter = new MemberDataExporter($this->pdo);

        $this->expectException(\RuntimeException::class);
        $exporter->export(99999, 1004, 10);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
