<?php
// ============================================================
// cli/test_integrations.php — Integration Health Check
//
// Diagnostyczny skrypt CLI sprawdzający per klub wszystkie skonfigurowane
// integracje (bramki płatności / shipping / Google Calendar / federacje)
// i raportujący stan w trybie human-friendly lub JSON.
//
// Po co:
//   - Po deploy: weryfikacja czy credentiale w produkcji działają.
//   - Cron (co godzinę): alert mailowy gdy któraś integracja zerwie się.
//   - Onboarding nowego klubu: szybki sanity check po wprowadzeniu kluczy.
//
// Bez tego: trzeba klikać przycisk "Test połączenia" w UI dla każdego
// klubu i każdej integracji — niepraktyczne przy >10 klubach.
//
// Użycie:
//   php cli/test_integrations.php                       # wszystkie kluby
//   php cli/test_integrations.php --club=1              # tylko jeden klub
//   php cli/test_integrations.php --integration=stripe  # tylko jedna integracja
//   php cli/test_integrations.php --json                # output JSON
//   php cli/test_integrations.php --verbose             # szczegóły każdej odpowiedzi
//   php cli/test_integrations.php --fail-on-error       # exit 1 gdy fail (cron)
//   php cli/test_integrations.php --timeout=5           # HTTP timeout per call (sek.)
//
// Exit codes:
//   0 — wszystko OK (lub bez --fail-on-error)
//   1 — co najmniej jeden test fail (gdy --fail-on-error)
//   2 — błąd inicjalizacji (DB, autoload)
// ============================================================
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

$vendor = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) require $vendor;

$helpers = ROOT_PATH . '/app/Helpers/Helpers.php';
if (file_exists($helpers)) require_once $helpers;

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

// ── Argv parser ────────────────────────────────────────────────
$opts = [
    'club'          => null,
    'integration'   => null,
    'verbose'       => false,
    'json'          => false,
    'fail_on_error' => false,
    'timeout'       => 5,
];
foreach ($argv ?? [] as $a) {
    if ($a === '--verbose')        { $opts['verbose'] = true; continue; }
    if ($a === '--json')           { $opts['json'] = true; continue; }
    if ($a === '--fail-on-error')  { $opts['fail_on_error'] = true; continue; }
    if (str_starts_with($a, '--club='))        { $opts['club'] = (int)substr($a, 7); continue; }
    if (str_starts_with($a, '--integration=')) { $opts['integration'] = strtolower(substr($a, 14)); continue; }
    if (str_starts_with($a, '--timeout='))     { $opts['timeout'] = max(1, (int)substr($a, 10)); continue; }
    if ($a === '--help' || $a === '-h') {
        echo "Usage: php cli/test_integrations.php [--club=N] [--integration=stripe|p24|payu|tpay|inpost|gcal|federation] [--verbose] [--json] [--fail-on-error] [--timeout=N]\n";
        exit(0);
    }
}

// Globalny default_socket_timeout dla streamów (cURL ma własny timeout w adapterach)
@ini_set('default_socket_timeout', (string)$opts['timeout']);

// ── Init DB ─────────────────────────────────────────────────────
try {
    $db = \App\Helpers\Database::pdo();
} catch (\Throwable $e) {
    fwrite(STDERR, "Init error: " . $e->getMessage() . "\n");
    exit(2);
}

$health = new IntegrationsHealthCheck($db, $opts);
$results = $health->run();

if ($opts['json']) {
    echo $health->formatJson($results) . "\n";
} else {
    echo $health->formatHuman($results);
}

if ($opts['fail_on_error'] && $results['summary']['failed'] > 0) {
    exit(1);
}
exit(0);


/**
 * Główna klasa wykonująca health check.
 *
 * Iteruje aktywne kluby, dla każdego sprawdza:
 *   1. Bramki płatności (Stripe / Przelewy24 / PayU / Tpay) — przez GatewayFactory
 *   2. Shipping (InPost) — bezpośrednio InPostAdapter
 *   3. Google Calendar — GoogleCalendarClient::testConnection()
 *   4. Federacje (PZPN / PZSS / PZKosz / PZLA / inne) — FederationExporterFactory
 *
 * Wynik per integracja:
 *   - 'ok'              — testConnection() zwrócił ok=true
 *   - 'fail'            — testConnection() zwrócił ok=false / wyjątek
 *   - 'not_configured'  — brak wiersza w DB / pusty credentials
 *
 * Pomija integracje "manual" (przelew tradycyjny — nie ma API).
 */
final class IntegrationsHealthCheck
{
    private const PAYMENT_PROVIDERS = ['stripe', 'przelewy24', 'payu', 'tpay'];
    /** Aliasy CLI --integration=... → wewnętrzne klucze grup. */
    private const INTEGRATION_ALIASES = [
        'p24'         => 'przelewy24',
        'przelewy24'  => 'przelewy24',
        'stripe'      => 'stripe',
        'payu'        => 'payu',
        'tpay'        => 'tpay',
        'inpost'      => 'inpost',
        'gcal'        => 'gcal',
        'google'      => 'gcal',
        'federation'  => 'federation',
        'federations' => 'federation',
    ];

    /** @var array<string,mixed> */
    private array $opts;

    public function __construct(
        private \PDO $db,
        array $opts,
    ) {
        $this->opts = $opts;
    }

    /** @return array{summary:array<string,mixed>, clubs:array<int,array<string,mixed>>} */
    public function run(): array
    {
        $started = microtime(true);

        $clubs  = $this->loadClubs();
        $report = [];

        $totalChecked  = 0;
        $totalOk       = 0;
        $totalFail     = 0;
        $totalSkipped  = 0;
        $failedSummary = [];

        $integrationFilter = $this->opts['integration'] !== null
            ? (self::INTEGRATION_ALIASES[$this->opts['integration']] ?? $this->opts['integration'])
            : null;

        // Progress bar w STDERR (chyba że --json).
        $useProgress = !$this->opts['json'] && function_exists('posix_isatty') && @posix_isatty(STDERR);

        $idx = 0;
        $totalClubs = count($clubs);
        foreach ($clubs as $club) {
            $idx++;
            if ($useProgress) {
                fwrite(STDERR, sprintf("\r[%d/%d] %s%s", $idx, $totalClubs, $this->shorten((string)$club['name'], 40), str_repeat(' ', 20)));
            }

            $clubId   = (int)$club['id'];
            $clubName = (string)$club['name'];

            // ClubContext::set robi side effect na sesji — w CLI sesja nie istnieje,
            // ale ClubScopedModel:clubId() i tak czyta z ClubContext::current()
            // (wewn. Session::get). Wymusimy bypass: bezpośrednio operujemy
            // queries bez ClubContext, ALBO ustawimy sesję w CLI (Session::set).
            // Wybór: Session::set działa też w CLI (storage in-memory $_SESSION).
            @\App\Helpers\Session::set('club_id', $clubId);

            $clubResult = [
                'id'           => $clubId,
                'name'         => $clubName,
                'integrations' => [],
            ];

            // 1. Bramki płatności
            if ($integrationFilter === null || in_array($integrationFilter, ['stripe', 'przelewy24', 'payu', 'tpay'], true)) {
                $providers = $integrationFilter !== null && in_array($integrationFilter, self::PAYMENT_PROVIDERS, true)
                    ? [$integrationFilter]
                    : self::PAYMENT_PROVIDERS;
                foreach ($providers as $p) {
                    $clubResult['integrations'][$p] = $this->checkGateway($clubId, $p);
                }
            }

            // 2. Shipping (InPost)
            if ($integrationFilter === null || $integrationFilter === 'inpost') {
                $clubResult['integrations']['inpost'] = $this->checkInPost($clubId);
            }

            // 3. Google Calendar
            if ($integrationFilter === null || $integrationFilter === 'gcal') {
                $clubResult['integrations']['gcal'] = $this->checkGoogleCalendar($clubId);
            }

            // 4. Federacje
            if ($integrationFilter === null || $integrationFilter === 'federation') {
                foreach ($this->checkFederations($clubId) as $fedCode => $r) {
                    $clubResult['integrations']['federation:' . $fedCode] = $r;
                }
            }

            // Statystyki per klub
            foreach ($clubResult['integrations'] as $intKey => $intRes) {
                $status = $intRes['status'] ?? 'skip';
                if ($status === 'ok') {
                    $totalOk++;
                    $totalChecked++;
                } elseif ($status === 'fail') {
                    $totalFail++;
                    $totalChecked++;
                    $failedSummary[] = [
                        'club_id'     => $clubId,
                        'club_name'   => $clubName,
                        'integration' => $intKey,
                        'mode'        => $intRes['mode']   ?? null,
                        'error'       => $intRes['error']  ?? ($intRes['message'] ?? 'unknown'),
                    ];
                } else {
                    $totalSkipped++;
                }
            }

            $report[] = $clubResult;
        }

        if ($useProgress) {
            fwrite(STDERR, "\r" . str_repeat(' ', 80) . "\r");
        }

        // Cleanup ClubContext po pętli (defensywa — gdyby ktoś używał wyniku w testach).
        @\App\Helpers\Session::remove('club_id');

        $duration = round(microtime(true) - $started, 2);

        return [
            'summary' => [
                'total_clubs'          => $totalClubs,
                'checked'              => $totalChecked,
                'success'              => $totalOk,
                'failed'               => $totalFail,
                'not_configured'       => $totalSkipped,
                'duration_sec'         => $duration,
                'failed_details'       => $failedSummary,
            ],
            'clubs' => $report,
        ];
    }

    // ── Per-integration checkers ────────────────────────────────

    /** @return array<string,mixed> */
    private function checkGateway(int $clubId, string $provider): array
    {
        try {
            $config = (new \App\Models\ClubPaymentGatewayModel())->findByProvider($provider);
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'mode' => null, 'error' => 'DB error: ' . $e->getMessage()];
        }
        if (!$config) {
            return ['status' => 'not_configured', 'mode' => null];
        }
        if (empty($config['api_key']) && empty($config['api_secret']) && empty($config['crc_key'])) {
            return ['status' => 'not_configured', 'mode' => null];
        }
        $mode = !empty($config['is_sandbox']) ? 'sandbox' : 'prod';

        $adapter = \App\Helpers\Gateway\GatewayFactory::forProvider($provider, $config);
        if (!$adapter) {
            return ['status' => 'fail', 'mode' => $mode, 'error' => "no adapter for {$provider}"];
        }

        return $this->wrapTest(fn() => $adapter->testConnection(), $mode);
    }

    /** @return array<string,mixed> */
    private function checkInPost(int $clubId): array
    {
        try {
            $config = (new \App\Models\ClubShippingProviderModel())->findByProvider('inpost');
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'mode' => null, 'error' => 'DB error: ' . $e->getMessage()];
        }
        if (!$config || empty($config['api_token']) || empty($config['organization_id'])) {
            return ['status' => 'not_configured', 'mode' => null];
        }
        $mode = !empty($config['is_sandbox']) ? 'sandbox' : 'prod';

        // InPostAdapter nie ma testConnection() — wzorujemy się na ClubShippingController.
        // Endpoint /v1/points jest najlżejszy a wymaga prawidłowych headers (Bearer token).
        try {
            $adapter = new \App\Helpers\Shipping\InPostAdapter($config);
            $points  = $adapter->listPaczkomats('00-001', 1);
            return [
                'status'  => 'ok',
                'mode'    => $mode,
                'message' => 'Połączenie OK (' . count($points) . ' paczkomat(ów))',
                'details' => [
                    'organization_id' => (string)$config['organization_id'],
                    'sandbox'         => !empty($config['is_sandbox']),
                ],
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'mode' => $mode, 'error' => $e->getMessage()];
        }
    }

    /** @return array<string,mixed> */
    private function checkGoogleCalendar(int $clubId): array
    {
        $model = new \App\Models\ClubGoogleCalendarModel();
        $config = $model->decryptedConfig($clubId);
        if (!$config) {
            return ['status' => 'not_configured', 'mode' => null];
        }
        if (empty($config['access_token']) && empty($config['refresh_token'])) {
            return ['status' => 'not_configured', 'mode' => null, 'message' => 'OAuth nie ukończony'];
        }
        $mode = 'prod'; // Google Calendar nie ma sandbox/prod podziału.

        // Auto-refresh gdy token wygasł (token_expires_at w przeszłości i mamy refresh_token).
        try {
            $client = new \App\Helpers\Calendar\GoogleCalendarClient($config);
            if (!empty($config['token_expires_at']) && strtotime((string)$config['token_expires_at']) < time()
                && !empty($config['refresh_token'])
            ) {
                $fresh = $client->refreshAccessToken();
                // Nie persistujemy w teście — re-init client z nowym tokenem.
                $client = new \App\Helpers\Calendar\GoogleCalendarClient(array_merge($config, [
                    'access_token' => $fresh['access_token'],
                ]));
            }
            $result = $client->testConnection();
            $details = [
                'account'      => $config['google_account_email'] ?? null,
                'calendar_id'  => $config['calendar_id'] ?? null,
                'last_sync_at' => $config['last_sync_at'] ?? null,
            ];
            if (!empty($result['ok'])) {
                return [
                    'status'  => 'ok',
                    'mode'    => $mode,
                    'message' => (string)($result['message'] ?? 'OK'),
                    'details' => $details,
                ];
            }
            return [
                'status'  => 'fail',
                'mode'    => $mode,
                'error'   => (string)($result['message'] ?? 'unknown error'),
                'details' => $details,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'mode' => $mode, 'error' => $e->getMessage()];
        }
    }

    /** @return array<string, array<string,mixed>> mapowanie federation_code → result */
    private function checkFederations(int $clubId): array
    {
        $out = [];
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM club_federation_credentials WHERE club_id = ? ORDER BY federation_code"
            );
            $stmt->execute([$clubId]);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            return ['_error' => ['status' => 'fail', 'mode' => null, 'error' => 'DB error: ' . $e->getMessage()]];
        }

        foreach ($rows as $row) {
            $code = (string)$row['federation_code'];
            // Decrypt (poza ClubScopedModel context — analogicznie do run_federation_exports.php).
            $config = $row;
            foreach (['api_username', 'api_password', 'api_token'] as $f) {
                $enc = $row[$f . '_enc'] ?? null;
                $config[$f] = null;
                if (!empty($enc)) {
                    try {
                        $config[$f] = \App\Helpers\Encryption::decrypt((string)$enc);
                    } catch (\Throwable) {
                        // Stara plaintext / corrupted — zostawiamy null, raportujemy fail
                        $out[$code] = [
                            'status' => 'fail',
                            'mode'   => !empty($row['is_sandbox']) ? 'sandbox' : 'prod',
                            'error'  => "decrypt error: {$f}",
                        ];
                        continue 2;
                    }
                }
            }

            $hasCreds = !empty($config['api_username']) || !empty($config['api_token']);
            if (!$hasCreds) {
                $out[$code] = ['status' => 'not_configured', 'mode' => null];
                continue;
            }

            $mode = !empty($row['is_sandbox']) ? 'sandbox' : 'prod';
            $exporter = \App\Helpers\Federations\FederationExporterFactory::forCode($code, $config);
            if (!$exporter) {
                $out[$code] = ['status' => 'fail', 'mode' => $mode, 'error' => "no exporter for {$code}"];
                continue;
            }
            $out[$code] = $this->wrapTest(fn() => $exporter->testConnection(), $mode);
        }
        return $out;
    }

    /**
     * Wrapper egzekwujący kontrakt testConnection() → status/mode/details.
     * Łapie wyjątki, mapuje boolowy 'ok' na 'ok'/'fail'.
     *
     * @param callable():array<string,mixed> $fn
     * @return array<string,mixed>
     */
    private function wrapTest(callable $fn, ?string $mode): array
    {
        try {
            $r = $fn();
            if (!is_array($r)) {
                return ['status' => 'fail', 'mode' => $mode, 'error' => 'adapter returned non-array'];
            }
            if (!empty($r['ok'])) {
                return [
                    'status'  => 'ok',
                    'mode'    => $mode,
                    'message' => (string)($r['message'] ?? 'OK'),
                    'details' => $r['details'] ?? [],
                ];
            }
            return [
                'status'  => 'fail',
                'mode'    => $mode,
                'error'   => (string)($r['message'] ?? 'unknown error'),
                'details' => $r['details'] ?? [],
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'mode' => $mode, 'error' => $e->getMessage()];
        }
    }

    /** @return array<int, array{id:int,name:string}> */
    private function loadClubs(): array
    {
        $sql = "SELECT id, name FROM clubs WHERE is_active = 1";
        $params = [];
        if ($this->opts['club'] !== null) {
            $sql .= " AND id = ?";
            $params[] = $this->opts['club'];
        }
        $sql .= " ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Output formatting ────────────────────────────────────────

    /** @param array<string,mixed> $results */
    public function formatJson(array $results): string
    {
        return json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<string,mixed> $results */
    public function formatHuman(array $results): string
    {
        $tty   = function_exists('posix_isatty') && @posix_isatty(STDOUT);
        $color = fn(string $c, string $s): string => $tty ? "\033[{$c}m{$s}\033[0m" : $s;
        $bar   = str_repeat('=', 60);

        $out  = "\n{$bar}\n";
        $out .= "ClubDesk — Integration Health Check\n";
        $out .= "{$bar}\n";

        foreach ($results['clubs'] as $c) {
            $out .= sprintf("Klub: %s (id=%d)\n", $c['name'], $c['id']);
            foreach ($c['integrations'] as $key => $r) {
                $icon  = $this->iconFor($key);
                $label = $this->labelFor($key);
                $mode  = !empty($r['mode']) ? '[' . strtoupper((string)$r['mode']) . ']' : '[—]';

                $statusStr = match ($r['status']) {
                    'ok'             => $color('32', 'OK   '),
                    'fail'           => $color('31', 'FAIL '),
                    'not_configured' => $color('90', 'SKIP '),
                    default          => '?    ',
                };
                $marker = match ($r['status']) {
                    'ok'             => $color('32', '[OK]'),
                    'fail'           => $color('31', '[FAIL]'),
                    'not_configured' => $color('90', '[--]'),
                    default          => '[??]',
                };

                $msg = '';
                if ($r['status'] === 'ok') {
                    $msg = (string)($r['message'] ?? 'OK');
                    if (!empty($r['details']) && $this->opts['verbose']) {
                        $msg .= ' ' . $this->compactDetails((array)$r['details']);
                    }
                } elseif ($r['status'] === 'fail') {
                    $msg = (string)($r['error'] ?? 'fail');
                } else {
                    $msg = 'Not configured';
                }

                $out .= sprintf(
                    "  %s %-14s %-10s %s %s\n",
                    $icon, $label, $mode, $marker, $msg
                );
            }
            $out .= "\n";
        }

        $s = $results['summary'];
        $out .= "{$bar}\n";
        $out .= "Summary\n";
        $out .= "{$bar}\n";
        $out .= sprintf("Total clubs:          %d\n", $s['total_clubs']);
        $out .= sprintf("Integrations checked: %d\n", $s['checked']);
        $out .= sprintf("%s  Success:           %d\n", $color('32', 'OK '), $s['success']);
        $out .= sprintf("%s  Failed:            %d\n", $color('31', '!! '), $s['failed']);
        $out .= sprintf("%s  Not configured:    %d\n", $color('90', '-- '), $s['not_configured']);
        $out .= sprintf("Duration:             %ss\n", $s['duration_sec']);

        if (!empty($s['failed_details'])) {
            $out .= "\nFailed integrations:\n";
            $i = 1;
            foreach ($s['failed_details'] as $f) {
                $out .= sprintf(
                    "  %d. %s — %s %s: %s\n",
                    $i++,
                    $f['club_name'],
                    $this->labelFor((string)$f['integration']),
                    $f['mode'] ? '(' . $f['mode'] . ')' : '',
                    $f['error']
                );
            }
        }

        return $out . "\n";
    }

    private function iconFor(string $key): string
    {
        if (str_starts_with($key, 'federation:')) return 'F';
        return match ($key) {
            'stripe', 'przelewy24', 'payu', 'tpay' => '$',
            'inpost'                               => '>',
            'gcal'                                 => '@',
            default                                => '?',
        };
    }

    private function labelFor(string $key): string
    {
        if (str_starts_with($key, 'federation:')) {
            return substr($key, strlen('federation:'));
        }
        return match ($key) {
            'stripe'     => 'Stripe',
            'przelewy24' => 'Przelewy24',
            'payu'       => 'PayU',
            'tpay'       => 'Tpay',
            'inpost'     => 'InPost',
            'gcal'       => 'Google Cal',
            default      => $key,
        };
    }

    /** @param array<string,mixed> $details */
    private function compactDetails(array $details): string
    {
        $parts = [];
        foreach ($details as $k => $v) {
            if (is_scalar($v) && $v !== '' && $v !== null) {
                $parts[] = $k . '=' . $this->shorten((string)$v, 30);
            }
        }
        return $parts ? '(' . implode(', ', $parts) . ')' : '';
    }

    private function shorten(string $s, int $max): string
    {
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }
}
