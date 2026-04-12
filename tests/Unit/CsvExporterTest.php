<?php

namespace Tests\Unit;

use App\Helpers\CsvExporter;
use PHPUnit\Framework\TestCase;

class CsvExporterTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(CsvExporter::class));
    }

    public function testHasDownloadMethod(): void
    {
        $this->assertTrue(method_exists(CsvExporter::class, 'download'));
    }
}
