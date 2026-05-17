<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\CsvImporter;
use PHPUnit\Framework\TestCase;

/**
 * Testy importu członków klubu (sekretariat).
 *
 * CsvImporter::import() wymaga bazy MySQL — pomijamy go i sprawdzamy
 * wyłącznie deterministyczne helpery: detekcję separatora, parsowanie,
 * auto-mapowanie kolumn oraz walidację schematu.
 */
class MemberImportTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/clubdesk_memberimport_' . uniqid();
        @mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($this->tmpDir);
        }
    }

    private function write(string $name, string $content): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    // ─────────────────────────────────────────────────────────────────
    public function testDetectDelimiterPrefersSemicolon(): void
    {
        $path = $this->write('semicolon.csv', "imie;nazwisko;email\nJan;Kowalski;jan@example.com\n");
        $this->assertSame(';', CsvImporter::detectDelimiter($path));
    }

    public function testDetectDelimiterDetectsComma(): void
    {
        $path = $this->write('comma.csv', "first_name,last_name,email\nJan,Kowalski,jan@example.com\n");
        $this->assertSame(',', CsvImporter::detectDelimiter($path));
    }

    public function testDetectDelimiterDetectsTab(): void
    {
        $path = $this->write('tab.csv', "first_name\tlast_name\temail\nJan\tKowalski\tjan@example.com\n");
        $this->assertSame("\t", CsvImporter::detectDelimiter($path));
    }

    // ─────────────────────────────────────────────────────────────────
    public function testParseStripsUtf8Bom(): void
    {
        $bom    = "\xEF\xBB\xBF";
        $path   = $this->write('bom.csv', $bom . "imie;nazwisko\nJan;Kowalski\n");
        $parsed = CsvImporter::parse($path, ';');

        $this->assertSame(['imie', 'nazwisko'], $parsed['headers']);
        $this->assertCount(1, $parsed['rows']);
        $this->assertSame(['Jan', 'Kowalski'], $parsed['rows'][0]);
    }

    public function testParseSkipsEmptyRows(): void
    {
        $csv = "imie;nazwisko\nJan;Kowalski\n;\nAnna;Nowak\n";
        $path = $this->write('empty.csv', $csv);
        $parsed = CsvImporter::parse($path, ';');

        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('Anna', $parsed['rows'][1][0]);
    }

    public function testParseReturnsEmptyForMissingFile(): void
    {
        $parsed = CsvImporter::parse($this->tmpDir . '/nonexistent.csv', ';');
        $this->assertSame([], $parsed['headers']);
        $this->assertSame([], $parsed['rows']);
    }

    // ─────────────────────────────────────────────────────────────────
    public function testMapColumnsRecognisesPolishHeaders(): void
    {
        $mapping = CsvImporter::mapColumns(['imie', 'nazwisko', 'email', 'pesel', 'telefon']);

        $this->assertSame('first_name', $mapping['imie']);
        $this->assertSame('last_name',  $mapping['nazwisko']);
        $this->assertSame('email',      $mapping['email']);
        $this->assertSame('pesel',      $mapping['pesel']);
        $this->assertSame('phone',      $mapping['telefon']);
    }

    public function testMapColumnsRecognisesEnglishHeaders(): void
    {
        $mapping = CsvImporter::mapColumns(['first_name', 'last_name', 'email', 'phone']);

        $this->assertSame('first_name', $mapping['first_name']);
        $this->assertSame('last_name',  $mapping['last_name']);
        $this->assertSame('email',      $mapping['email']);
        $this->assertSame('phone',      $mapping['phone']);
    }

    public function testMapColumnsHandlesPolishDiacritics(): void
    {
        $mapping = CsvImporter::mapColumns(['imię', 'płeć']);
        $this->assertSame('first_name', $mapping['imię']);
        $this->assertSame('gender',     $mapping['płeć']);
    }

    public function testMapColumnsReturnsNullForUnknown(): void
    {
        $mapping = CsvImporter::mapColumns(['kompletnie_nieznana_kolumna']);
        $this->assertNull($mapping['kompletnie_nieznana_kolumna']);
    }

    // ─────────────────────────────────────────────────────────────────
    public function testDbColumnsContainsMandatoryFields(): void
    {
        $cols = CsvImporter::dbColumns();
        $required = ['first_name', 'last_name', 'email', 'pesel', 'phone',
                     'birth_date', 'gender', 'member_number', 'status'];
        foreach ($required as $r) {
            $this->assertContains(
                $r,
                $cols,
                "Brakuje kolumny '{$r}' w dbColumns() — szablon importu byłby niepełny."
            );
        }
    }

    public function testDbColumnsDoesNotExposeSystemFields(): void
    {
        $cols = CsvImporter::dbColumns();
        // Te pola nie mogą trafić do mapowania (mogłyby zostać nadpisane przez user input).
        $this->assertNotContains('id', $cols);
        $this->assertNotContains('club_id', $cols);
        $this->assertNotContains('created_by', $cols);
        $this->assertNotContains('created_at', $cols);
    }

    // ─────────────────────────────────────────────────────────────────
    public function testControllerClassExists(): void
    {
        $this->assertTrue(
            class_exists(\App\Controllers\MemberImportController::class),
            'MemberImportController musi istnieć dla dedykowanego flow sekretariatu.'
        );
    }

    public function testControllerExposesExpectedActions(): void
    {
        $methods = ['index', 'preview', 'execute', 'templateCsv', 'templateXlsx'];
        foreach ($methods as $m) {
            $this->assertTrue(
                method_exists(\App\Controllers\MemberImportController::class, $m),
                "Brakuje akcji {$m}() w MemberImportController"
            );
        }
    }

    public function testSekretariatDashboardControllerExists(): void
    {
        $this->assertTrue(
            class_exists(\App\Controllers\SekretariatDashboardController::class),
            'SekretariatDashboardController jest wymagany dla roli ksiegowy.'
        );
        $this->assertTrue(method_exists(\App\Controllers\SekretariatDashboardController::class, 'index'));
    }
}
