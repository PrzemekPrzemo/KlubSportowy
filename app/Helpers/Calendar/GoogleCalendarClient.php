<?php

declare(strict_types=1);

namespace App\Helpers\Calendar;

use RuntimeException;

/**
 * Niskopoziomowy klient Google OAuth2 + Calendar API v3 (raw cURL).
 *
 * Świadomie BEZ google/apiclient (waży 100MB+ deps). Endpoints:
 *   Auth:    https://accounts.google.com/o/oauth2/v2/auth
 *   Token:   POST https://oauth2.googleapis.com/token
 *   Refresh: POST https://oauth2.googleapis.com/token (grant_type=refresh_token)
 *   API:     https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events
 *
 * Config tablica (z ClubGoogleCalendarModel::decryptedConfig() lub mergem z
 * config/google.php):
 *   - client_id     (string)
 *   - client_secret (string)
 *   - access_token  (string|null)   — set po exchangeCode/refresh
 *   - refresh_token (string|null)
 *   - timezone      (string)        — domyślnie Europe/Warsaw
 *
 * Klient NIE persistuje tokenów — to robi ClubGoogleCalendarModel. Po
 * exchangeCode() / refreshAccessToken() caller dostaje świeże tokeny i
 * je zapisuje.
 */
class GoogleCalendarClient
{
    private const AUTH_URL    = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL   = 'https://oauth2.googleapis.com/token';
    private const API_BASE    = 'https://www.googleapis.com/calendar/v3';
    private const DEFAULT_SCOPE = 'https://www.googleapis.com/auth/calendar';

    /** @param array<string,mixed> $config */
    public function __construct(private array $config)
    {
    }

    /**
     * URL do redirectu usera w step 1 (consent screen).
     * `access_type=offline` + `prompt=consent` daje pewność że dostaniemy refresh_token.
     */
    public function authUrl(string $redirectUri, string $state = ''): string
    {
        $clientId = (string)($this->config['client_id'] ?? '');
        if ($clientId === '') {
            throw new RuntimeException('Google OAuth: brak client_id w konfiguracji.');
        }
        $params = [
            'client_id'              => $clientId,
            'redirect_uri'           => $redirectUri,
            'response_type'          => 'code',
            'scope'                  => (string)($this->config['scope'] ?? self::DEFAULT_SCOPE),
            'access_type'            => 'offline',
            'prompt'                 => 'consent',
            'include_granted_scopes' => 'true',
        ];
        if ($state !== '') {
            $params['state'] = $state;
        }
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Wymiana `code` (z callbacku) na access_token + refresh_token.
     *
     * @return array{access_token:string, refresh_token:?string, expires_in:int, token_type:string, scope:string}
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $resp = $this->httpForm(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => (string)($this->config['client_id']     ?? ''),
            'client_secret' => (string)($this->config['client_secret'] ?? ''),
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($resp['access_token'])) {
            $err = $resp['error_description'] ?? $resp['error'] ?? 'unknown_error';
            throw new RuntimeException('Google OAuth exchange failed: ' . $err);
        }
        $this->config['access_token']  = $resp['access_token'];
        if (!empty($resp['refresh_token'])) {
            $this->config['refresh_token'] = $resp['refresh_token'];
        }
        return [
            'access_token'  => (string)$resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? null,
            'expires_in'    => (int)($resp['expires_in'] ?? 3600),
            'token_type'    => (string)($resp['token_type'] ?? 'Bearer'),
            'scope'         => (string)($resp['scope'] ?? ''),
        ];
    }

    /**
     * Wymiana refresh_token na świeży access_token.
     * Zwraca [access_token, expires_in] — caller powinien zapisać.
     *
     * @return array{access_token:string, expires_in:int}
     */
    public function refreshAccessToken(): array
    {
        $refresh = (string)($this->config['refresh_token'] ?? '');
        if ($refresh === '') {
            throw new RuntimeException('Google OAuth: brak refresh_token — wymagane ponowne połączenie konta.');
        }

        $resp = $this->httpForm(self::TOKEN_URL, [
            'refresh_token' => $refresh,
            'client_id'     => (string)($this->config['client_id']     ?? ''),
            'client_secret' => (string)($this->config['client_secret'] ?? ''),
            'grant_type'    => 'refresh_token',
        ]);

        if (empty($resp['access_token'])) {
            $err = $resp['error_description'] ?? $resp['error'] ?? 'unknown_error';
            throw new RuntimeException('Google OAuth refresh failed: ' . $err);
        }
        $this->config['access_token'] = $resp['access_token'];
        return [
            'access_token' => (string)$resp['access_token'],
            'expires_in'   => (int)($resp['expires_in'] ?? 3600),
        ];
    }

    /**
     * Lista kalendarzy dostępnych dla obecnego access_token.
     * @return array<int, array<string,mixed>>
     */
    public function listCalendars(): array
    {
        $resp = $this->apiRequest('GET', '/users/me/calendarList');
        return (array)($resp['items'] ?? []);
    }

    /**
     * Tworzy event w kalendarzu. Zwraca pełny Google Event resource
     * (zawiera `id`, `etag`).
     *
     * @param array<string,mixed> $event
     * @return array<string,mixed>
     */
    public function createEvent(string $calendarId, array $event): array
    {
        return $this->apiRequest('POST', '/calendars/' . rawurlencode($calendarId) . '/events', $event);
    }

    /**
     * Update event — PATCH (częściowy update). Wymaga googleEventId.
     *
     * @param array<string,mixed> $event
     * @return array<string,mixed>
     */
    public function updateEvent(string $calendarId, string $eventId, array $event): array
    {
        return $this->apiRequest(
            'PATCH',
            '/calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId),
            $event
        );
    }

    /**
     * Usuwa event z Google Calendar.
     * 410 GONE traktowane jak success (już usunięty).
     */
    public function deleteEvent(string $calendarId, string $eventId): void
    {
        $this->apiRequest(
            'DELETE',
            '/calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId),
            null,
            allowEmpty: true,
            allowGone: true
        );
    }

    /**
     * Lista eventów z kalendarza. Obsługuje `updatedMin` (delta sync ISO8601)
     * lub `syncToken` (Google opaque, polecane do incremental).
     *
     * @return array<string,mixed> {items: [...], nextSyncToken?: '...', nextPageToken?: '...'}
     */
    public function listEvents(string $calendarId, ?string $updatedMin = null, ?string $syncToken = null, ?string $pageToken = null): array
    {
        $params = ['maxResults' => 250, 'singleEvents' => 'true', 'showDeleted' => 'true'];
        if ($syncToken !== null && $syncToken !== '') {
            $params['syncToken'] = $syncToken;
        } else {
            if ($updatedMin !== null && $updatedMin !== '') {
                $params['updatedMin'] = $updatedMin;
            }
        }
        if ($pageToken !== null && $pageToken !== '') {
            $params['pageToken'] = $pageToken;
        }
        $qs = http_build_query($params);
        return $this->apiRequest('GET', '/calendars/' . rawurlencode($calendarId) . '/events?' . $qs);
    }

    /**
     * Lekki test połączenia — pobiera listę kalendarzy.
     *
     * @return array{ok:bool, message:string, calendars?:int}
     */
    public function testConnection(): array
    {
        try {
            $items = $this->listCalendars();
            return [
                'ok'        => true,
                'message'   => 'Połączenie OK — znaleziono ' . count($items) . ' kalendarz(y).',
                'calendars' => count($items),
            ];
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'message' => 'Błąd: ' . $e->getMessage(),
            ];
        }
    }

    // ── Internals ───────────────────────────────────────────────────────

    /**
     * Wewnętrzny request do Calendar API v3. Auth przez Bearer access_token.
     *
     * @param array<string,mixed>|null $body
     * @return array<string,mixed>
     */
    private function apiRequest(
        string $method,
        string $path,
        ?array $body = null,
        bool $allowEmpty = false,
        bool $allowGone = false
    ): array {
        $token = (string)($this->config['access_token'] ?? '');
        if ($token === '') {
            throw new RuntimeException('Google Calendar: brak access_token.');
        }

        $url = self::API_BASE . $path;
        $ch  = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $err !== '') {
            throw new RuntimeException("Google Calendar cURL error: {$err}");
        }
        if ($allowGone && $code === 410) {
            return [];
        }
        if ($code === 204 || ($allowEmpty && (string)$resp === '')) {
            return [];
        }
        $data = json_decode((string)$resp, true);
        if (!is_array($data)) {
            $data = [];
        }
        if ($code < 200 || $code >= 300) {
            $msg = $data['error']['message'] ?? ('HTTP ' . $code);
            throw new RuntimeException('Google Calendar API ' . $code . ': ' . $msg);
        }
        return $data;
    }

    /**
     * POST form-urlencoded — używane dla token endpointu OAuth.
     *
     * @param array<string,string> $form
     * @return array<string,mixed>
     */
    private function httpForm(string $url, array $form): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($form),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $err !== '') {
            throw new RuntimeException("Google OAuth cURL error: {$err}");
        }
        $data = json_decode((string)$resp, true);
        if (!is_array($data)) {
            throw new RuntimeException('Google OAuth: invalid JSON response (HTTP ' . $code . ')');
        }
        if ($code < 200 || $code >= 300) {
            $msg = $data['error_description'] ?? $data['error'] ?? ('HTTP ' . $code);
            throw new RuntimeException('Google OAuth ' . $code . ': ' . (string)$msg);
        }
        return $data;
    }
}
