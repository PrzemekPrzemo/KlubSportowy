<?php

namespace App\Helpers\Federations;

/**
 * Wspólny klient HTTP dla adapterów federacji, które zbierają dane przez
 * scraping publicznych portali.
 *
 * Zasady:
 *   - User-Agent: "ClubDesk Bot/1.0 (+https://clubdesk.pl)" — identyfikujemy
 *     się jawnie, żeby admini portali wiedzieli kto puka.
 *   - Rate limit: min. 5 sekund pomiędzy requestami do tej samej domeny
 *     (per-process, pamiętany w storage/cache/federation_rl_*.json).
 *   - Cache odpowiedzi: domyślnie 1h, plikowy. Idempotentność — kolejne
 *     wywołania tego samego URL w cache TTL zwracają tę samą treść.
 *   - Robots.txt: best-effort sprawdzenie ścieżki przed pobraniem (cached).
 *   - Graceful errors: nigdy nie rzuca wyjątkiem, zwraca null przy błędzie.
 *
 * Ten helper jest celowo cienki — adaptery same parsują HTML (DOMDocument).
 */
class FederationScrapingClient
{
    public const USER_AGENT = 'ClubDesk Bot/1.0 (+https://clubdesk.pl)';
    public const DEFAULT_CACHE_TTL = 3600; // 1h
    public const MIN_INTERVAL_SECONDS = 5;
    public const TIMEOUT = 15;

    private string $cacheDir;
    /** Robots.txt cache w pamięci procesu (per-domain → array of disallow paths). */
    private array $robotsCache = [];

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir
            ?? (defined('ROOT_PATH') ? ROOT_PATH . '/storage/cache' : dirname(__DIR__, 3) . '/storage/cache');
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * Pobierz URL przez GET. Zwraca treść HTML/JSON jako string albo null.
     *
     * @param string $url         pełny URL (https://…)
     * @param int    $cacheTtl    TTL cache w sekundach; 0 = bez cache
     * @param array  $extraHeaders dodatkowe nagłówki HTTP (Accept, Cookie, etc.)
     */
    public function get(string $url, int $cacheTtl = self::DEFAULT_CACHE_TTL, array $extraHeaders = []): ?string
    {
        // 1. Cache hit
        if ($cacheTtl > 0) {
            $cached = $this->cacheGet($url, $cacheTtl);
            if ($cached !== null) {
                return $cached;
            }
        }

        // 2. Robots check
        if (!$this->robotsAllows($url)) {
            return null;
        }

        // 3. Rate limit per-domain
        $domain = parse_url($url, PHP_URL_HOST) ?: '';
        if ($domain !== '') {
            $this->rateLimit($domain, self::MIN_INTERVAL_SECONDS);
        }

        // 4. Faktyczny request
        $body = $this->doCurlGet($url, $extraHeaders);

        if ($body !== null && $cacheTtl > 0) {
            $this->cacheSet($url, $body);
        }
        return $body;
    }

    /**
     * Buduje DOMDocument z HTML, tolerancyjny na malformed markup.
     */
    public function parseDom(string $html): \DOMDocument
    {
        $doc = new \DOMDocument();
        // Suppress warnings — większość polskich portali ma HTML z błędami.
        $prev = libxml_use_internal_errors(true);
        // Wymuś UTF-8 — DOMDocument inaczej zinterpretuje cp-1250.
        $prefix = '<?xml encoding="UTF-8">';
        @$doc->loadHTML($prefix . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $doc;
    }

    /**
     * Rate-limiter per-domain: usypia proces tak, by ostatni request do tej
     * domeny był co najmniej $minSeconds temu. Zapisuje znacznik w pliku
     * storage/cache/federation_rl_<host>.json.
     */
    public function rateLimit(string $domain, int $minSeconds = self::MIN_INTERVAL_SECONDS): void
    {
        $file = $this->cacheDir . '/federation_rl_' . preg_replace('/[^a-z0-9._-]/i', '_', $domain) . '.txt';
        $now = microtime(true);

        if (is_file($file)) {
            $last = (float)@file_get_contents($file);
            $elapsed = $now - $last;
            if ($elapsed < $minSeconds) {
                $sleep = (int)ceil(($minSeconds - $elapsed) * 1_000_000);
                if ($sleep > 0 && $sleep < 30_000_000) { // safety cap 30s
                    usleep($sleep);
                }
            }
        }
        @file_put_contents($file, (string)microtime(true), LOCK_EX);
    }

    // ---------------------------------------------------------------- private

    private function doCurlGet(string $url, array $extraHeaders): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        $headers = array_merge([
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pl,en;q=0.7',
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_ENCODING       => '', // accept gzip
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            return null;
        }
        return is_string($body) ? $body : null;
    }

    private function cacheKey(string $url): string
    {
        return $this->cacheDir . '/federation_' . hash('sha256', $url) . '.cache';
    }

    private function cacheGet(string $url, int $ttl): ?string
    {
        $f = $this->cacheKey($url);
        if (!is_file($f)) return null;
        if ((time() - filemtime($f)) > $ttl) return null;
        $data = @file_get_contents($f);
        return $data === false ? null : $data;
    }

    private function cacheSet(string $url, string $body): void
    {
        @file_put_contents($this->cacheKey($url), $body, LOCK_EX);
    }

    /**
     * Bardzo prosty parser robots.txt — sprawdza tylko grupę User-agent: *
     * i listę Disallow:. Cached per-domain w pamięci procesu (+ plikowy cache
     * sam robots.txt przez get()).
     */
    private function robotsAllows(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) return true;
        $host = $parts['host'];
        $path = $parts['path'] ?? '/';

        if (!isset($this->robotsCache[$host])) {
            $robotsUrl = ($parts['scheme'] ?? 'https') . '://' . $host . '/robots.txt';
            // Bezpośredni curl bez nawracającego robotsAllows() — uniknij infinite loop.
            $body = $this->cacheGet($robotsUrl, 86400); // 1 day
            if ($body === null) {
                $body = $this->doCurlGet($robotsUrl, []);
                if ($body !== null) $this->cacheSet($robotsUrl, $body);
            }
            $this->robotsCache[$host] = $body ? $this->parseRobots($body) : [];
        }

        foreach ($this->robotsCache[$host] as $disallow) {
            if ($disallow === '') continue;
            if (str_starts_with($path, $disallow)) {
                return false;
            }
        }
        return true;
    }

    /** Parser robots.txt → zwraca listę Disallow dla User-agent: * (najprostsza forma). */
    private function parseRobots(string $body): array
    {
        $rules = [];
        $applies = false;
        foreach (preg_split('/\r?\n/', $body) ?: [] as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line) ?? '');
            if ($line === '') continue;
            if (stripos($line, 'User-agent:') === 0) {
                $ua = trim(substr($line, 11));
                $applies = ($ua === '*');
                continue;
            }
            if ($applies && stripos($line, 'Disallow:') === 0) {
                $rules[] = trim(substr($line, 9));
            }
        }
        return $rules;
    }
}
