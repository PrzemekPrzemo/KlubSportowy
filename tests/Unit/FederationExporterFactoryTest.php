<?php

namespace Tests\Unit;

use App\Helpers\Federations\FederationExporterFactory;
use App\Helpers\Federations\FederationExporterInterface;
use App\Helpers\Federations\GenericCsvExporter;
use App\Helpers\Federations\PzhlAdapter;
use App\Helpers\Federations\PzjAdapter;
use App\Helpers\Federations\PzkarateAdapter;
use App\Helpers\Federations\PzkolAdapter;
use App\Helpers\Federations\PzkoszAdapter;
use App\Helpers\Federations\PzlaAdapter;
use App\Helpers\Federations\PznpAdapter;
use App\Helpers\Federations\PzpsAdapter;
use App\Helpers\Federations\PzssAdapter;
use App\Helpers\Federations\PzszachAdapter;
use App\Helpers\Federations\PztaekAdapter;
use App\Helpers\Federations\PztsAdapter;
use App\Helpers\Federations\PzwAdapter;
use App\Helpers\Federations\PzbAdapter;
use App\Helpers\Federations\PzpAdapter;
use App\Helpers\Federations\PztenAdapter;
use App\Helpers\Federations\ZprpAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Routing factory + status metadata. Bez DB i bez network.
 */
class FederationExporterFactoryTest extends TestCase
{
    public function testForCodeRoutesToCorrectAdapter(): void
    {
        $this->assertInstanceOf(PznpAdapter::class,    FederationExporterFactory::forCode('PZPN', []));
        $this->assertInstanceOf(PzssAdapter::class,    FederationExporterFactory::forCode('PZSS', []));
        $this->assertInstanceOf(PzkoszAdapter::class,  FederationExporterFactory::forCode('PZKosz', []));
        $this->assertInstanceOf(PzlaAdapter::class,    FederationExporterFactory::forCode('PZLA', []));
        $this->assertInstanceOf(PzhlAdapter::class,    FederationExporterFactory::forCode('PZHL', []));
        $this->assertInstanceOf(PzpsAdapter::class,    FederationExporterFactory::forCode('PZPS', []));
        $this->assertInstanceOf(PztsAdapter::class,    FederationExporterFactory::forCode('PZTS', []));
        $this->assertInstanceOf(PzwAdapter::class,     FederationExporterFactory::forCode('PZW', []));
        $this->assertInstanceOf(PzjAdapter::class,     FederationExporterFactory::forCode('PZJ', []));
        $this->assertInstanceOf(PztaekAdapter::class,   FederationExporterFactory::forCode('PZTAEK', []));
        $this->assertInstanceOf(PzkarateAdapter::class, FederationExporterFactory::forCode('PZKARATE', []));
        $this->assertInstanceOf(PzkolAdapter::class,    FederationExporterFactory::forCode('PZKOL', []));
        $this->assertInstanceOf(PzszachAdapter::class,  FederationExporterFactory::forCode('PZSZACH', []));
        $this->assertInstanceOf(ZprpAdapter::class,    FederationExporterFactory::forCode('ZPRP', []));
        $this->assertInstanceOf(PzpAdapter::class,     FederationExporterFactory::forCode('PZP', []));
        $this->assertInstanceOf(PztenAdapter::class,   FederationExporterFactory::forCode('PZTEN', []));
        $this->assertInstanceOf(PzbAdapter::class,     FederationExporterFactory::forCode('PZB', []));
    }

    public function testForCodeFallsBackToGenericForUnknown(): void
    {
        $adapter = FederationExporterFactory::forCode('PZBRYDŻ', []);
        $this->assertInstanceOf(GenericCsvExporter::class, $adapter);
        $this->assertSame('PZBRYDŻ', $adapter->federationCode());
        $this->assertSame(FederationExporterInterface::STATUS_CSV_ONLY, $adapter->adapterStatus());
    }

    public function testNormalizationOfCode(): void
    {
        // case-insensitive routing
        $this->assertInstanceOf(PzssAdapter::class, FederationExporterFactory::forCode('pzss', []));
        $this->assertInstanceOf(PzssAdapter::class, FederationExporterFactory::forCode('  PzSs  ', []));
    }

    public function testSupportedCodesContainsAllAdapters(): void
    {
        $codes = FederationExporterFactory::supportedCodes();
        foreach (['PZPN','PZSS','PZKosz','PZLA','PZHL','PZPS','PZTS','PZW','PZJ','PZTAEK','PZKARATE','PZKOL','PZSZACH','ZPRP','PZP','PZTEN','PZB'] as $c) {
            $this->assertArrayHasKey($c, $codes, "Missing $c in supportedCodes");
            $this->assertNotEmpty($codes[$c]);
        }
    }

    public function testSupportedMetadataIncludesStatus(): void
    {
        $meta = FederationExporterFactory::supportedWithMetadata();
        foreach ($meta as $code => $info) {
            $this->assertArrayHasKey('label', $info, "$code missing label");
            $this->assertArrayHasKey('status', $info, "$code missing status");
            $this->assertContains($info['status'], [
                FederationExporterInterface::STATUS_SCRAPING,
                FederationExporterInterface::STATUS_LOGIN,
                FederationExporterInterface::STATUS_API,
                FederationExporterInterface::STATUS_CSV_ONLY,
                FederationExporterInterface::STATUS_STUB,
            ], "$code has invalid status: {$info['status']}");
        }
    }

    public function testAdapterStatusMatchesMetadata(): void
    {
        foreach (FederationExporterFactory::supportedWithMetadata() as $code => $info) {
            $adapter = FederationExporterFactory::forCode($code, []);
            $this->assertNotNull($adapter, "no adapter for $code");
            $this->assertSame(
                $info['status'],
                $adapter->adapterStatus(),
                "adapter $code reports {$adapter->adapterStatus()} but metadata says {$info['status']}"
            );
        }
    }

    public function testIsSupported(): void
    {
        $this->assertTrue(FederationExporterFactory::isSupported('PZSS'));
        $this->assertTrue(FederationExporterFactory::isSupported('pzss'));
        $this->assertTrue(FederationExporterFactory::isSupported('PZHL'));
        $this->assertFalse(FederationExporterFactory::isSupported('PZBRYDŻ'));
    }

    public function testFederationCodesAreCorrect(): void
    {
        $this->assertSame('PZPN',   FederationExporterFactory::forCode('PZPN',   [])->federationCode());
        $this->assertSame('PZSS',   FederationExporterFactory::forCode('PZSS',   [])->federationCode());
        $this->assertSame('PZKosz', FederationExporterFactory::forCode('PZKosz', [])->federationCode());
        $this->assertSame('PZLA',   FederationExporterFactory::forCode('PZLA',   [])->federationCode());
        $this->assertSame('PZHL',   FederationExporterFactory::forCode('PZHL',   [])->federationCode());
        $this->assertSame('PZPS',   FederationExporterFactory::forCode('PZPS',   [])->federationCode());
        $this->assertSame('PZTS',   FederationExporterFactory::forCode('PZTS',   [])->federationCode());
        $this->assertSame('PZW',    FederationExporterFactory::forCode('PZW',    [])->federationCode());
        $this->assertSame('PZJ',    FederationExporterFactory::forCode('PZJ',    [])->federationCode());
        $this->assertSame('PZTAEK',   FederationExporterFactory::forCode('PZTAEK',   [])->federationCode());
        $this->assertSame('PZKARATE', FederationExporterFactory::forCode('PZKARATE', [])->federationCode());
        $this->assertSame('PZKOL',    FederationExporterFactory::forCode('PZKOL',    [])->federationCode());
        $this->assertSame('PZSZACH',  FederationExporterFactory::forCode('PZSZACH',  [])->federationCode());
        $this->assertSame('ZPRP',   FederationExporterFactory::forCode('ZPRP',   [])->federationCode());
        $this->assertSame('PZP',    FederationExporterFactory::forCode('PZP',    [])->federationCode());
        $this->assertSame('PZTEN',  FederationExporterFactory::forCode('PZTEN',  [])->federationCode());
        $this->assertSame('PZB',    FederationExporterFactory::forCode('PZB',    [])->federationCode());
    }

    public function testBatchAAdaptersMetadata(): void
    {
        $meta = FederationExporterFactory::supportedWithMetadata();

        $this->assertSame('Związek Piłki Ręcznej w Polsce', $meta['ZPRP']['label']);
        $this->assertSame(FederationExporterInterface::STATUS_SCRAPING, $meta['ZPRP']['status']);

        $this->assertSame('Polski Związek Pływacki', $meta['PZP']['label']);
        $this->assertSame(FederationExporterInterface::STATUS_SCRAPING, $meta['PZP']['status']);

        $this->assertSame('Polski Związek Tenisowy', $meta['PZTEN']['label']);
        $this->assertSame(FederationExporterInterface::STATUS_SCRAPING, $meta['PZTEN']['status']);

        $this->assertSame('Polski Związek Bokserski', $meta['PZB']['label']);
        $this->assertSame(FederationExporterInterface::STATUS_LOGIN, $meta['PZB']['status']);
    }

    public function testBatchAAdaptersAreNormalizedCaseInsensitive(): void
    {
        $this->assertInstanceOf(ZprpAdapter::class,  FederationExporterFactory::forCode('zprp', []));
        $this->assertInstanceOf(PzpAdapter::class,   FederationExporterFactory::forCode('pzp', []));
        $this->assertInstanceOf(PztenAdapter::class, FederationExporterFactory::forCode('pzten', []));
        $this->assertInstanceOf(PzbAdapter::class,   FederationExporterFactory::forCode('pzb', []));
    }
}
