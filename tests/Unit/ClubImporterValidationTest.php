<?php

namespace Tests\Unit;

use App\Helpers\Backup\ClubImporter;
use PDO;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Walidacja paczek importu — testowane bez bazy MySQL (SQLite in-memory).
 *
 * Sprawdzamy:
 *   - brak manifest.json -> invalid + error
 *   - nieobslugiwany format_version -> invalid
 *   - poprawny manifest -> valid
 *   - schema_version w przyszlosci -> error
 */
class ClubImporterValidationTest extends TestCase
{
    private string $tmpDir;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/clubdesk_import_test_' . uniqid();
        @mkdir($this->tmpDir, 0700, true);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Importer.validate() prubuje SELECT MAX(version) FROM schema_migrations
        // — gdy tabela nie istnieje, leci do fallbacku (glob migracji), co dziala.
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir . '/*') ?: [] as $f) @unlink($f);
            @rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    public function testValidateMissingFileReturnsError(): void
    {
        $importer = new ClubImporter($this->pdo);
        $result = $importer->validate($this->tmpDir . '/nonexistent.zip');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertNull($result['manifest']);
    }

    public function testValidateZipWithoutManifestReturnsError(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive not available');
        }
        $path = $this->tmpDir . '/no_manifest.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('data/members.json', '[]');
        $zip->close();

        $importer = new ClubImporter($this->pdo);
        $result = $importer->validate($path);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('manifest', strtolower($result['errors'][0]));
    }

    public function testValidateUnsupportedFormatVersion(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive not available');
        }
        $path = $this->tmpDir . '/bad_version.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('manifest.json', json_encode([
            'format_version' => '2.5',
            'schema_version' => '050',
            'club_id'        => 1,
        ]));
        $zip->close();

        $importer = new ClubImporter($this->pdo);
        $result = $importer->validate($path);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $found = false;
        foreach ($result['errors'] as $e) {
            if (stripos($e, 'format_version') !== false) { $found = true; break; }
        }
        $this->assertTrue($found, 'Spodziewany blad o format_version');
    }

    public function testValidateAcceptsCorrectManifest(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive not available');
        }
        $path = $this->tmpDir . '/good.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('manifest.json', json_encode([
            'format_version' => '1.0',
            'schema_version' => '001', // bardzo stary -> warning, ale valid
            'club_id'        => 1,
            'club_name'      => 'Test',
            'exported_at'    => '2026-05-17T12:00:00+02:00',
            'table_counts'   => ['members' => 2],
        ]));
        $zip->close();

        $importer = new ClubImporter($this->pdo);
        $result = $importer->validate($path);

        $this->assertTrue($result['valid'], 'Errors: ' . implode('; ', $result['errors']));
        $this->assertIsArray($result['manifest']);
        $this->assertSame('1.0', $result['manifest']['format_version']);
    }

    public function testValidateFutureSchemaVersionFails(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive not available');
        }
        $path = $this->tmpDir . '/future.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('manifest.json', json_encode([
            'format_version' => '1.0',
            'schema_version' => '999',
            'club_id'        => 1,
        ]));
        $zip->close();

        $importer = new ClubImporter($this->pdo);
        $result = $importer->validate($path);

        $this->assertFalse($result['valid']);
        $found = false;
        foreach ($result['errors'] as $e) {
            if (stripos($e, 'schema_version') !== false || stripos($e, 'nowszy') !== false) {
                $found = true; break;
            }
        }
        $this->assertTrue($found, 'Spodziewany blad o nowszym schema_version');
    }
}
