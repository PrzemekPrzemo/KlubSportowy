<?php

namespace Tests\Unit;

use App\Helpers\Federations\FederationScrapingClient;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Testy FederationScrapingClient — funkcje czyste (parseDom, rateLimit, cache).
 * Nie wykonuje rzeczywistych requestów HTTP.
 */
class FederationScrapingClientTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/fed_scrape_test_' . uniqid();
        @mkdir($this->tempDir, 0775, true);
    }

    protected function tearDown(): void
    {
        // Cleanup
        foreach (glob($this->tempDir . '/*') ?: [] as $f) @unlink($f);
        @rmdir($this->tempDir);
    }

    public function testParseDomReturnsDocumentForValidHtml(): void
    {
        $client = new FederationScrapingClient($this->tempDir);
        $doc = $client->parseDom('<html><body><h1>Test</h1></body></html>');
        $this->assertInstanceOf(\DOMDocument::class, $doc);
        $h1s = $doc->getElementsByTagName('h1');
        $this->assertSame(1, $h1s->length);
        $this->assertSame('Test', $h1s->item(0)->textContent);
    }

    public function testParseDomHandlesMalformedHtml(): void
    {
        $client = new FederationScrapingClient($this->tempDir);
        $doc = $client->parseDom('<html><body><h1>Broken<p>unclosed</body>');
        $this->assertInstanceOf(\DOMDocument::class, $doc);
    }

    public function testParseDomHandlesUtf8Polish(): void
    {
        $client = new FederationScrapingClient($this->tempDir);
        $doc = $client->parseDom('<html><body><h1>Łucznik Łódź</h1></body></html>');
        $h1 = $doc->getElementsByTagName('h1')->item(0);
        $this->assertNotNull($h1);
        $this->assertStringContainsString('Łucznik', $h1->textContent);
    }

    public function testRateLimitWritesMarkerAndDoesNotSleepOnFirstCall(): void
    {
        $client = new FederationScrapingClient($this->tempDir);
        $start = microtime(true);
        $client->rateLimit('example.com', 5);
        $elapsed = microtime(true) - $start;
        $this->assertLessThan(0.5, $elapsed, 'First rate-limit call should not sleep');
        $marker = $this->tempDir . '/federation_rl_example.com.txt';
        $this->assertFileExists($marker);
    }

    public function testRateLimitDoesNotSleepIfEnoughTimeElapsed(): void
    {
        $client = new FederationScrapingClient($this->tempDir);
        $marker = $this->tempDir . '/federation_rl_example.com.txt';
        // Symuluj że ostatni request był 10s temu
        file_put_contents($marker, (string)(microtime(true) - 10));
        $start = microtime(true);
        $client->rateLimit('example.com', 5);
        $elapsed = microtime(true) - $start;
        $this->assertLessThan(0.5, $elapsed);
    }

    public function testCachedContentIsReturnedWithoutRefetch(): void
    {
        $client = new FederationScrapingClient($this->tempDir);
        // Prepopulate cache manually
        $url = 'https://example.com/test';
        $cacheFile = $this->tempDir . '/federation_' . hash('sha256', $url) . '.cache';
        file_put_contents($cacheFile, '<html>cached content</html>');

        // 1-godzinny TTL — plik świeży
        $body = $client->get($url, 3600);
        $this->assertSame('<html>cached content</html>', $body);
    }

    public function testExpiredCacheIsIgnored(): void
    {
        $client = new FederationScrapingClient($this->tempDir);
        $url = 'https://example.invalid-tld-no-resolve/test';
        $cacheFile = $this->tempDir . '/federation_' . hash('sha256', $url) . '.cache';
        file_put_contents($cacheFile, 'old');
        // Mark file as old (2h ago)
        touch($cacheFile, time() - 7200);

        // TTL 1h → expired → tries to fetch → invalid domain → returns null gracefully.
        // Test sprawdza że brak crash'a, a stara wartość nie jest zwracana.
        $body = $client->get($url, 3600);
        $this->assertNotSame('old', $body);
    }
}
