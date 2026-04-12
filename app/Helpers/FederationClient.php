<?php

namespace App\Helpers;

/**
 * Klient integracji z polskimi związkami sportowymi.
 *
 * Każda federacja (PZSS, PZPN, PZKosz, PZPS, PZLA, PZW...) ma swoje API/portal.
 * Integracja konfigurowalna per-klub (club_settings.federation_*).
 *
 * Aktualnie wspierane:
 *   PZSS  — weryfikacja licencji strzeleckiej (scraping portalu)
 *   PZPN  — lookup zawodnika w Extranet (API jeśli dostępne)
 *   Inne  — generyczny sprawdzacz statusu (URL do portalu federacji)
 *
 * Konfiguracja per-klub:
 *   club_settings.federation_pzss_login   = login do portalu PZSS
 *   club_settings.federation_pzss_pass    = hasło (zaszyfrowane)
 *   club_settings.federation_pzpn_club_id = numer klubu w PZPN
 *   club_settings.federation_api_key      = klucz API (gdzie dostępny)
 */
class FederationClient
{
    /**
     * Weryfikacja statusu licencji PZSS.
     * Zwraca array z informacjami o licencji lub null w razie błędu.
     */
    public static function pzssCheckLicense(string $licenseNumber, ?int $clubId = null): ?array
    {
        $config = self::getConfig($clubId, 'pzss');
        $url = 'https://www.pzss.org.pl/patenty-licencje/licencja-zawodnicza';

        // Attempt scraping — jeśli dostępne credentiale do portalu
        if (!empty($config['login']) && !empty($config['pass'])) {
            return self::pzssScrape($licenseNumber, $config);
        }

        // Fallback: link do ręcznej weryfikacji
        return [
            'license_number' => $licenseNumber,
            'status'         => 'unknown',
            'verify_url'     => $url,
            'message'        => 'Automatyczna weryfikacja wymaga credentiali do portalu PZSS. Zweryfikuj ręcznie.',
        ];
    }

    /**
     * Lookup zawodnika w PZPN Extranet.
     */
    public static function pzpnLookup(string $pesel, ?int $clubId = null): ?array
    {
        $config = self::getConfig($clubId, 'pzpn');

        if (empty($config['api_key'])) {
            return [
                'status'     => 'not_configured',
                'verify_url' => 'https://www.laczynaspilka.pl/',
                'message'    => 'Integracja PZPN wymaga klucza API. Skonfiguruj w ustawieniach klubu.',
            ];
        }

        // PZPN API call (jeśli API istnieje)
        $ch = curl_init('https://api.pzpn.pl/v1/players/search');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $config['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['pesel' => $pesel]),
            CURLOPT_POST       => true,
            CURLOPT_TIMEOUT    => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            return $data ?: ['status' => 'empty_response'];
        }

        return [
            'status'     => 'error',
            'http_code'  => $code,
            'message'    => 'Błąd połączenia z API PZPN.',
        ];
    }

    /**
     * Generyczny sprawdzacz — zwraca URL portalu federacji do ręcznej weryfikacji.
     */
    public static function getFederationPortalUrl(string $federationCode): ?string
    {
        return match (strtoupper($federationCode)) {
            'PZSS'    => 'https://www.pzss.org.pl/patenty-licencje/',
            'PZPN'    => 'https://www.laczynaspilka.pl/',
            'PZKOSZ'  => 'https://www.pzkosz.pl/',
            'PZPS'    => 'https://www.pzps.pl/',
            'PZLA'    => 'https://www.pzla.pl/',
            'PZHL'    => 'https://www.pzhl.org.pl/',
            'PZPR'    => 'https://www.zprp.pl/',
            'PZT'     => 'https://www.pzt.pl/',
            'PZP'     => 'https://www.polswim.pl/',
            'PZW'     => 'https://www.pzw.org.pl/',
            'PZJ'     => 'https://www.pzjudo.pl/',
            'PZKARATE'=> 'https://www.pzkarate.pl/',
            default   => null,
        };
    }

    /**
     * Weryfikacja licencji w dowolnej federacji — dispatcher.
     */
    public static function verifyLicense(string $federationCode, string $licenseNumber, ?int $clubId = null): array
    {
        return match (strtoupper($federationCode)) {
            'PZSS' => self::pzssCheckLicense($licenseNumber, $clubId) ?? ['status' => 'error'],
            'PZPN' => ['status' => 'redirect', 'url' => self::getFederationPortalUrl('PZPN'), 'message' => 'Sprawdź na portalu PZPN'],
            default => [
                'status'  => 'manual',
                'url'     => self::getFederationPortalUrl($federationCode),
                'message' => 'Zweryfikuj na portalu federacji ' . $federationCode,
            ],
        };
    }

    /**
     * Lista wszystkich wspieranych federacji z ich statusem integracji.
     */
    public static function supportedIntegrations(): array
    {
        return [
            'PZSS'    => ['name' => 'Polski Związek Strzelectwa Sportowego', 'level' => 'scraping',  'features' => ['license_verify']],
            'PZPN'    => ['name' => 'Polski Związek Piłki Nożnej',           'level' => 'api_ready', 'features' => ['player_lookup', 'license_verify']],
            'PZKosz'  => ['name' => 'Polski Związek Koszykówki',             'level' => 'manual',    'features' => ['portal_link']],
            'PZPS'    => ['name' => 'Polski Związek Piłki Siatkowej',        'level' => 'manual',    'features' => ['portal_link']],
            'PZLA'    => ['name' => 'Polski Związek Lekkiej Atletyki',       'level' => 'manual',    'features' => ['portal_link']],
            'PZHL'    => ['name' => 'Polski Związek Hokeja na Lodzie',       'level' => 'manual',    'features' => ['portal_link']],
            'PZPR'    => ['name' => 'Polski Związek Piłki Ręcznej',          'level' => 'manual',    'features' => ['portal_link']],
            'PZT'     => ['name' => 'Polski Związek Tenisowy',               'level' => 'manual',    'features' => ['portal_link']],
            'PZP'     => ['name' => 'Polski Związek Pływacki',               'level' => 'manual',    'features' => ['portal_link']],
            'PZW'     => ['name' => 'Polski Związek Wrotkarski',             'level' => 'manual',    'features' => ['portal_link']],
            'PZJ'     => ['name' => 'Polski Związek Judo',                   'level' => 'manual',    'features' => ['portal_link']],
            'PZKarate'=> ['name' => 'Polski Związek Karate',                 'level' => 'manual',    'features' => ['portal_link']],
        ];
    }

    // ── Private ──────────────────────────────────────────────

    private static function getConfig(?int $clubId, string $federation): array
    {
        $cs = new \App\Models\ClubSettingsModel();
        $prefix = 'federation_' . strtolower($federation) . '_';
        if ($clubId === null) return [];
        return [
            'login'   => $cs->get($clubId, $prefix . 'login', ''),
            'pass'    => $cs->get($clubId, $prefix . 'pass', ''),
            'api_key' => $cs->get($clubId, $prefix . 'api_key', ''),
            'club_id' => $cs->get($clubId, $prefix . 'club_id', ''),
        ];
    }

    /**
     * Próba scrapingu portalu PZSS — proof-of-concept.
     * W produkcji wymaga regularnego testowania (portale zmieniają strukturę).
     */
    private static function pzssScrape(string $licenseNumber, array $config): ?array
    {
        $ch = curl_init('https://system.pzss.pl/api/license/check');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode([
                'license_number' => $licenseNumber,
                'login'          => $config['login'] ?? '',
                'password'       => $config['pass'] ?? '',
            ]),
            CURLOPT_POST       => true,
            CURLOPT_TIMEOUT    => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            if ($data) {
                return [
                    'license_number' => $licenseNumber,
                    'status'         => $data['status'] ?? 'unknown',
                    'valid_until'    => $data['valid_until'] ?? null,
                    'holder_name'    => $data['name'] ?? null,
                    'federation'     => 'PZSS',
                    'source'         => 'api',
                ];
            }
        }

        return [
            'license_number' => $licenseNumber,
            'status'         => 'connection_error',
            'error'          => $error ?: "HTTP {$code}",
            'message'        => 'Nie udało się połączyć z portalem PZSS. Zweryfikuj ręcznie.',
            'verify_url'     => 'https://www.pzss.org.pl/patenty-licencje/',
        ];
    }
}
