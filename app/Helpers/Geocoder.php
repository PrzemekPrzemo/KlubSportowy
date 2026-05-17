<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Geocoder — adres -> lat/lng poprzez OpenStreetMap Nominatim.
 *
 * Polityka uzycia Nominatim (https://operations.osmfoundation.org/policies/nominatim/):
 *   - Rate-limit MAX 1 req/sec (enforced via sleep przed wywolaniem)
 *   - User-Agent / Referer wymagany (identyfikacja aplikacji)
 *   - Brak heavy use w produkcji bez dedicated instance (dla MVP OK)
 *   - Wyniki cachowane (uzywamy file-cache 30 dni per address)
 *
 * Zwracana struktura: ['lat' => float, 'lng' => float] lub null gdy brak wyniku/blad.
 */
class Geocoder
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT    = 'ClubDesk Discovery/1.0 (+https://clubdesk.pl)';
    private const CACHE_TTL     = 30 * 24 * 3600; // 30 dni
    private const TIMEOUT_SEC   = 8;

    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? (ROOT_PATH . '/storage/cache');
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * Resolve address → ['lat'=>..,'lng'=>..] albo null.
     *
     * @param string $address Pelny adres ('ul. Marszalkowska 1, Warszawa').
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $cacheFile = $this->cacheFilePath($address);
        $cached    = $this->readCache($cacheFile);
        if ($cached !== null) {
            // Cache moze byc rowniez "negatywny" (zapisana wartosc null)
            return $cached['result'] ?? null;
        }

        $result = $this->fetchFromNominatim($address);
        $this->writeCache($cacheFile, ['result' => $result, 'addr' => $address, 'at' => time()]);
        return $result;
    }

    private function cacheFilePath(string $address): string
    {
        $key = hash('sha256', mb_strtolower($address, 'UTF-8'));
        return $this->cacheDir . '/geocode_' . $key . '.json';
    }

    /** @return array{result: array{lat: float, lng: float}|null, addr: string, at: int}|null */
    private function readCache(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }
        if ((time() - filemtime($file)) > self::CACHE_TTL) {
            @unlink($file);
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function writeCache(string $file, array $payload): void
    {
        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /** @return array{lat: float, lng: float}|null */
    private function fetchFromNominatim(string $address): ?array
    {
        // Rate-limit: Nominatim wymaga max 1 req/sec.
        // Minimal best-effort sleep — wystarczy jak wolanie nie idzie w petli z innych helperow.
        @usleep(1_100_000); // 1.1 s

        $url = self::NOMINATIM_URL . '?' . http_build_query([
            'q'              => $address,
            'format'         => 'json',
            'limit'          => 1,
            'addressdetails' => 0,
            'countrycodes'   => 'pl',
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => "User-Agent: " . self::USER_AGENT . "\r\n"
                                 . "Accept: application/json\r\n",
                'timeout'       => self::TIMEOUT_SEC,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false || $body === '') {
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json) || empty($json[0]) || !isset($json[0]['lat'], $json[0]['lon'])) {
            return null;
        }

        return [
            'lat' => round((float)$json[0]['lat'], 6),
            'lng' => round((float)$json[0]['lon'], 6),
        ];
    }
}
