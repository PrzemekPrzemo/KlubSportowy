<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Calendar\GoogleCalendarClient;
use App\Helpers\Calendar\GoogleCalendarSyncer;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Feature;
use App\Helpers\Session;
use App\Models\ClubGoogleCalendarModel;

/**
 * Per-klub konfiguracja + OAuth + manual sync trigger dla Google Calendar.
 *
 * Routes (wszystkie /club/google-calendar/* — zarząd/admin only):
 *   GET  /club/google-calendar             — status integracji + UI
 *   GET  /club/google-calendar/connect     — redirect do Google OAuth consent
 *   GET  /club/google-calendar/callback    — OAuth callback (?code=...)
 *   POST /club/google-calendar/save        — manualne ustawienia (calendar_id, direction)
 *   POST /club/google-calendar/test        — test połączenia (JSON)
 *   POST /club/google-calendar/sync-now    — manual sync trigger
 *   POST /club/google-calendar/disconnect  — clear tokens + dezaktywacja
 *
 * Feature flag guard: `google_calendar_sync`.
 */
class ClubGoogleCalendarController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
        Feature::requireEnabled('google_calendar_sync');
    }

    public function index(): void
    {
        $model  = new ClubGoogleCalendarModel();
        $config = $model->findForClub();

        $this->render('club_google_calendar/index', [
            'title'  => 'Synchronizacja Google Calendar',
            'config' => $config,
        ]);
    }

    /**
     * Step 1 OAuth: redirect do Google consent screen.
     * Generuje state token z CSRF (anti-CSRF dla OAuth flow).
     */
    public function connect(): void
    {
        $cfg = $this->resolveOAuthClient();
        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            Session::flash('error', 'Brak konfiguracji OAuth client (ustaw GOOGLE_OAUTH_CLIENT_ID / SECRET).');
            $this->redirect('club/google-calendar');
        }

        $state = bin2hex(random_bytes(16));
        Session::set('gcal_oauth_state', $state);

        $client = new GoogleCalendarClient([
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'scope'         => $cfg['scope'] ?? 'https://www.googleapis.com/auth/calendar',
        ]);

        $redirectUri = $this->redirectUri();
        header('Location: ' . $client->authUrl($redirectUri, $state));
        exit;
    }

    /**
     * Step 2 OAuth: callback z Google z `?code=...`.
     */
    public function callback(): void
    {
        $error = (string)($_GET['error'] ?? '');
        if ($error !== '') {
            Session::flash('error', 'OAuth zakończony błędem: ' . $error);
            $this->redirect('club/google-calendar');
        }

        $code  = (string)($_GET['code']  ?? '');
        $state = (string)($_GET['state'] ?? '');
        $expectedState = (string)(Session::get('gcal_oauth_state') ?? '');

        if ($code === '' || $state === '' || !hash_equals($expectedState, $state)) {
            Session::flash('error', 'Nieprawidłowy state OAuth (możliwe CSRF).');
            $this->redirect('club/google-calendar');
        }
        Session::set('gcal_oauth_state', null);

        $cfg = $this->resolveOAuthClient();
        $client = new GoogleCalendarClient([
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
        ]);

        try {
            $tokens = $client->exchangeCode($code, $this->redirectUri());
        } catch (\Throwable $e) {
            Session::flash('error', 'Wymiana code → token nie powiodła się: ' . $e->getMessage());
            $this->redirect('club/google-calendar');
        }

        $clubId = (int)ClubContext::require();
        $model  = new ClubGoogleCalendarModel();

        // Upewnij się że wiersz istnieje (upsert z minimalnymi danymi)
        $existing = $model->findForClub();
        if (!$existing) {
            $model->upsert([
                'calendar_id'     => 'primary',
                'sync_direction'  => 'push',
                'is_active'       => 1,
            ]);
        } else {
            $model->upsert(['is_active' => 1]);
        }

        // Pobierz e-mail konta Google (z access_token przez userinfo nie używamy
        // — zamiast tego po prostu kalendarz primary "summary").
        $email = $this->fetchAccountEmail($tokens['access_token']);

        $model->updateTokens(
            $clubId,
            $tokens['access_token'],
            $tokens['refresh_token'] ?? null,
            $tokens['expires_in'],
            $email
        );

        Session::flash('success', 'Konto Google połączone pomyślnie' . ($email ? " ({$email})" : '') . '.');
        $this->redirect('club/google-calendar');
    }

    public function save(): void
    {
        Csrf::verify();

        $calendarId    = trim((string)($_POST['calendar_id']    ?? 'primary')) ?: 'primary';
        $syncDirection = (string)($_POST['sync_direction'] ?? 'push');
        if (!in_array($syncDirection, ['push', 'pull', 'both'], true)) {
            $syncDirection = 'push';
        }
        $clientId     = trim((string)($_POST['client_id']     ?? ''));
        $clientSecret = trim((string)($_POST['client_secret'] ?? ''));
        $isActive     = isset($_POST['is_active']) ? 1 : 0;

        $data = [
            'calendar_id'    => mb_substr($calendarId, 0, 120),
            'sync_direction' => $syncDirection,
            'client_id'      => mb_substr($clientId, 0, 255),
            'is_active'      => $isActive,
        ];
        if ($clientSecret !== '') {
            $data['client_secret'] = $clientSecret;
        }

        (new ClubGoogleCalendarModel())->upsert($data);

        Session::flash('success', 'Konfiguracja zapisana.');
        $this->redirect('club/google-calendar');
    }

    public function testConnection(): void
    {
        Csrf::verify();

        $clubId = (int)ClubContext::require();
        $model  = new ClubGoogleCalendarModel();
        $cfg    = $model->decryptedConfig($clubId);
        if (!$cfg || empty($cfg['access_token'])) {
            $this->json(['success' => false, 'message' => 'Brak access_token — połącz konto Google.'], 422);
        }

        $client = new GoogleCalendarClient($cfg);

        // Refresh jeśli wygasł.
        $expTs = $cfg['token_expires_at'] ? strtotime((string)$cfg['token_expires_at']) : 0;
        if ($expTs < time() + 60 && !empty($cfg['refresh_token'])) {
            try {
                $fresh = $client->refreshAccessToken();
                $model->updateTokens($clubId, $fresh['access_token'], null, $fresh['expires_in']);
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => 'Refresh tokenu nie powiódł się: ' . $e->getMessage()], 422);
            }
        }

        $result = $client->testConnection();
        $this->json([
            'success' => (bool)($result['ok'] ?? false),
            'message' => $result['message'] ?? '',
            'details' => $result,
        ], ($result['ok'] ?? false) ? 200 : 422);
    }

    public function syncNow(): void
    {
        Csrf::verify();
        $clubId = (int)ClubContext::require();

        $result = GoogleCalendarSyncer::syncClub($clubId);

        $msg = sprintf(
            'Sync OK: pushed=%d, updated=%d, pulled=%d, deleted=%d',
            $result['pushed'], $result['updated'], $result['pulled'], $result['deleted']
        );
        if (!empty($result['errors'])) {
            $msg .= ' — błędy: ' . count($result['errors']);
            Session::flash('error', $msg . ' (' . implode('; ', array_slice($result['errors'], 0, 3)) . ')');
        } else {
            Session::flash('success', $msg);
        }
        $this->redirect('club/google-calendar');
    }

    public function disconnect(): void
    {
        Csrf::verify();
        $clubId = (int)ClubContext::require();
        (new ClubGoogleCalendarModel())->disconnect($clubId);
        Session::flash('success', 'Konto Google odłączone. Tokeny usunięte.');
        $this->redirect('club/google-calendar');
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * Łączy per-klub client_id/secret z globalnymi z config/google.php.
     *
     * @return array<string,mixed>
     */
    private function resolveOAuthClient(): array
    {
        $clubId = (int)ClubContext::require();
        $cfg = (new ClubGoogleCalendarModel())->decryptedConfig($clubId)
            ?? require dirname(__DIR__, 2) . '/config/google.php';

        // Jeśli decryptedConfig zwrócił rekord ale puste creds → fallback global.
        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            $globalCfgFile = dirname(__DIR__, 2) . '/config/google.php';
            if (is_file($globalCfgFile)) {
                $global = require $globalCfgFile;
                $cfg['client_id']     = $cfg['client_id']     ?: ($global['client_id']     ?? '');
                $cfg['client_secret'] = $cfg['client_secret'] ?: ($global['client_secret'] ?? '');
                $cfg['scope']         = $cfg['scope']         ?? ($global['scope']         ?? '');
            }
        }
        return $cfg;
    }

    private function redirectUri(): string
    {
        $globalCfgFile = dirname(__DIR__, 2) . '/config/google.php';
        $global = is_file($globalCfgFile) ? require $globalCfgFile : [];
        $explicit = (string)($global['redirect_uri'] ?? '');
        if ($explicit !== '') {
            return $explicit;
        }
        // Auto-detect z BASE_URL.
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        return $base . '/club/google-calendar/callback';
    }

    /**
     * Best-effort: pobiera e-mail konta Google z userinfo endpointu.
     */
    private function fetchAccountEmail(string $accessToken): ?string
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code !== 200) {
            return null;
        }
        $data = json_decode((string)$resp, true);
        if (!is_array($data) || empty($data['email'])) {
            return null;
        }
        return (string)$data['email'];
    }
}
