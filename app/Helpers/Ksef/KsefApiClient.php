<?php

declare(strict_types=1);

namespace App\Helpers\Ksef;

use App\Helpers\Database;
use App\Helpers\Encryption;
use RuntimeException;

/**
 * KSeF (Krajowy System e-Faktur) API client — Phase 1 foundation.
 *
 * Portuje minimalne MVP z Billu-System (KsefApiService.php) — tylko warstwa
 * "connection foundation": challenge + init session + status + terminate.
 *
 * Faza 2 doda: send invoice, get invoice status, query, batch.
 * Faza 3 doda: XAdES signing, certyfikat-based auth (bez tokenu).
 *
 * Endpoints (interactive online v1 API):
 *   - test: https://ksef-test.mf.gov.pl/api
 *   - prod: https://ksef.mf.gov.pl/api
 *
 * Wszystkie wywołania są stateless (HTTP + JSON) — żadnego cache, żadnych
 * sesji w pamięci procesu. Wywołujący przekazuje session token uzyskany z
 * initSession() do kolejnych requestów (terminateSession / status).
 *
 * Multi-tenant: testConnection(clubId) ładuje config WYŁĄCZNIE z
 * club_ksef_config WHERE club_id = ? i odszyfrowuje token przez
 * Encryption::decryptForClub(token, clubId). Wynik logowany do
 * ksef_audit_log z action='connection_test'.
 *
 * Bezpieczeństwo:
 *   - SSL_VERIFYPEER + SSL_VERIFYHOST zawsze włączone (no opt-out).
 *   - Timeouty (connect 5s, total 30s) — żeby nie blokować workerów.
 *   - Token NIE jest nigdy zwracany w response do UI.
 */
final class KsefApiClient
{
    public const MODE_TEST = 'test';
    public const MODE_PROD = 'prod';

    private const BASE_URLS = [
        self::MODE_TEST => 'https://ksef-test.mf.gov.pl/api',
        self::MODE_PROD => 'https://ksef.mf.gov.pl/api',
    ];

    private const CONNECT_TIMEOUT_S = 5;
    private const TOTAL_TIMEOUT_S   = 30;
    private const USER_AGENT        = 'ClubDesk-KSeF/1.0 (+phase1-foundation)';

    private string $baseUrl;

    public function __construct(string $mode = self::MODE_TEST)
    {
        if (!isset(self::BASE_URLS[$mode])) {
            throw new RuntimeException("Unknown KSeF mode: {$mode}");
        }
        $this->baseUrl = self::BASE_URLS[$mode];
    }

    // ── Niskopoziomowe wywołania API ─────────────────────────────

    /**
     * POST /online/Session/AuthorisationChallenge
     *
     * Zwraca strukturę z challenge string + timestamp, niezbędną do
     * podpisania SessionToken (krok następny: XAdES sign challenge lub
     * RSA-OAEP wraps token z public key urzędu, w zależności od auth method).
     *
     * @return array{challenge: string, timestamp: string}
     */
    public function authChallenge(string $nip): array
    {
        $payload = [
            'contextIdentifier' => [
                'type'       => 'onip',  // identyfikator NIP
                'identifier' => $nip,
            ],
        ];
        $resp = $this->request('POST', '/online/Session/AuthorisationChallenge', $payload);
        $challenge = (string)($resp['challenge'] ?? '');
        $timestamp = (string)($resp['timestamp'] ?? '');
        if ($challenge === '' || $timestamp === '') {
            throw new RuntimeException('KSeF challenge: missing fields in response');
        }
        return ['challenge' => $challenge, 'timestamp' => $timestamp];
    }

    /**
     * POST /online/Session/InitToken
     *
     * Wysyła zaszyfrowany (przez public key MF) blob SessionToken.
     * Zwraca sessionToken (string) który należy przekazać w kolejnych
     * requestach w nagłówku SessionToken.
     *
     * UWAGA: w Phase 1 ten endpoint NIE jest jeszcze wywoływany przez UI —
     * wymaga albo (a) certyfikatu kwalifikowanego do XAdES (Phase 3),
     * albo (b) publicznego klucza MF do RSA-OAEP wrap (Phase 2).
     * Pozostawione w API klienta dla kompletności (test integracyjny może
     * zmockować zaszyfrowany blob).
     *
     * @param string $encryptedTokenXml zaszyfrowany SessionToken (base64 lub XML)
     *
     * @return array{sessionToken: array{token: string}, referenceNumber: string}
     */
    public function initSession(string $encryptedTokenXml): array
    {
        // Body to RAW XML/binary z zaszyfrowanym tokenem. KSeF API oczekuje
        // application/octet-stream dla wariantu RSA, lub Content-Type:
        // application/xml dla wariantu XAdES. W Phase 1 zakładamy octet-stream.
        return $this->rawRequest(
            'POST',
            '/online/Session/InitToken',
            $encryptedTokenXml,
            ['Content-Type: application/octet-stream'],
        );
    }

    /**
     * GET /online/Session/Status — health-check sesji.
     *
     * @return array<string,mixed>
     */
    public function getSessionStatus(string $sessionToken): array
    {
        return $this->request('GET', '/online/Session/Status', null, [
            'SessionToken: ' . $sessionToken,
        ]);
    }

    /**
     * DELETE /online/Session — zamyka aktywną sesję KSeF.
     */
    public function terminateSession(string $sessionToken): bool
    {
        $this->request('DELETE', '/online/Session', null, [
            'SessionToken: ' . $sessionToken,
        ]);
        return true;
    }

    // ── High-level: connection test dla klubu ────────────────────

    /**
     * Pełny smoke-test integracji KSeF dla podanego klubu.
     *
     * Strategia Phase 1: wywołujemy WYŁĄCZNIE authChallenge — jest to
     * najmniej inwazyjne wywołanie (nie wymaga session tokenu) i pozwala
     * zweryfikować, że:
     *   1) klub ma uzupełniony NIP,
     *   2) NIP jest zarejestrowany w KSeF,
     *   3) sieć / SSL do KSeF działa,
     *   4) tryb (test/prod) jest dostępny.
     *
     * Pełna sekwencja initSession + status + terminate wymaga
     * zaszyfrowanego SessionToken (XAdES lub RSA-OAEP), co jest poza
     * scope Phase 1 i zostanie dodane w Phase 3.
     *
     * @return array{ok: bool, message: string, response_time_ms?: int}
     */
    public function testConnection(int $clubId): array
    {
        $cfg = $this->loadConfig($clubId);
        if ($cfg === null) {
            return ['ok' => false, 'message' => 'Brak konfiguracji KSeF dla klubu.'];
        }
        if (empty($cfg['nip'])) {
            return ['ok' => false, 'message' => 'NIP klubu nie został skonfigurowany.'];
        }
        if (!preg_match('/^\d{10}$/', (string)$cfg['nip'])) {
            return ['ok' => false, 'message' => 'NIP ma nieprawidłowy format (oczekiwano 10 cyfr).'];
        }

        // Switch base URL na tryb z configu
        $this->baseUrl = self::BASE_URLS[$cfg['mode']] ?? self::BASE_URLS[self::MODE_TEST];

        $start = microtime(true);
        try {
            $challenge = $this->authChallenge((string)$cfg['nip']);
            $ms        = (int)round((microtime(true) - $start) * 1000);

            $msg = sprintf(
                'OK — KSeF (%s) odpowiada (challenge=%s..., %dms).',
                strtoupper((string)$cfg['mode']),
                substr($challenge['challenge'], 0, 8),
                $ms,
            );
            return ['ok' => true, 'message' => $msg, 'response_time_ms' => $ms];
        } catch (\Throwable $e) {
            $ms = (int)round((microtime(true) - $start) * 1000);
            return [
                'ok'               => false,
                'message'          => 'Błąd KSeF: ' . $e->getMessage(),
                'response_time_ms' => $ms,
            ];
        }
    }

    // ── Helpery ──────────────────────────────────────────────────

    /**
     * Ładuje skonfigurowane parametry KSeF + odszyfrowuje sekrety.
     *
     * @return array{
     *   nip:?string, mode:string, enabled:int,
     *   api_token:?string, cert_password:?string, cert_path:?string,
     * }|null
     */
    private function loadConfig(int $clubId): ?array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT enabled, mode, nip, api_token_encrypted, cert_path,
                    cert_password_encrypted, authorized_subject_identifier
               FROM club_ksef_config
              WHERE club_id = ?
              LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        if (!$row) return null;

        return [
            'enabled'       => (int)$row['enabled'],
            'mode'          => (string)$row['mode'],
            'nip'           => $row['nip'] !== null ? (string)$row['nip'] : null,
            'api_token'     => Encryption::decryptForClub($row['api_token_encrypted'] ?? null, $clubId),
            'cert_path'     => $row['cert_path'] !== null ? (string)$row['cert_path'] : null,
            'cert_password' => Encryption::decryptForClub($row['cert_password_encrypted'] ?? null, $clubId),
        ];
    }

    /**
     * Generic JSON request — kompresuje boilerplate cURL.
     *
     * @param array<string,mixed>|null $body  null = GET/DELETE bez body
     * @param array<int,string>        $extraHeaders
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, ?array $body = null, array $extraHeaders = []): array
    {
        $rawBody = $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($body !== null) {
            $extraHeaders[] = 'Content-Type: application/json';
        }
        return $this->rawRequest($method, $path, $rawBody, $extraHeaders);
    }

    /**
     * Surowe wywołanie HTTP do KSeF API. Zwraca zdekodowane JSON (lub
     * pustą tablicę dla pustego body z 200/204).
     *
     * @param array<int,string> $extraHeaders
     * @return array<string,mixed>
     */
    private function rawRequest(string $method, string $path, ?string $rawBody, array $extraHeaders = []): array
    {
        $url = $this->baseUrl . $path;
        $ch  = curl_init();
        if ($ch === false) {
            throw new RuntimeException('curl_init() failed');
        }

        $headers = array_merge([
            'Accept: application/json',
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_S,
            CURLOPT_TIMEOUT        => self::TOTAL_TIMEOUT_S,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($rawBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        }

        $resp     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $err !== '') {
            throw new RuntimeException("KSeF transport error: {$err}");
        }
        if ($httpCode >= 500) {
            throw new RuntimeException("KSeF server error (HTTP {$httpCode})");
        }
        if ($httpCode >= 400) {
            $decoded = json_decode((string)$resp, true);
            $msg     = is_array($decoded)
                ? ($decoded['exception']['exceptionDetailList'][0]['exceptionDescription']
                    ?? $decoded['message']
                    ?? ('HTTP ' . $httpCode))
                : 'HTTP ' . $httpCode;
            throw new RuntimeException("KSeF API: {$msg}");
        }

        if ($resp === '' || $resp === null) {
            return [];
        }
        $decoded = json_decode((string)$resp, true);
        if (!is_array($decoded)) {
            // Niektóre endpointy (np. DELETE Session) zwracają pusty body
            return [];
        }
        return $decoded;
    }
}
