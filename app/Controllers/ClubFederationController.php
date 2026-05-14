<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Federations\FederationException;
use App\Helpers\Federations\FederationExporterFactory;
use App\Helpers\Federations\MemberPayload;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\ClubFederationCredentialModel;
use App\Models\FederationExportLogModel;
use App\Models\MemberModel;

/**
 * Per-klub konfiguracja FederationExporter — credentials do federacji
 * sportowych (PZPN/PZSS/PZKosz/PZLA/…). Wzorowane na ClubGatewayController.
 *
 * Pola wrażliwe (api_username, api_password, api_token) szyfrowane
 * AES-256-GCM przez App\Helpers\Encryption — w ClubFederationCredentialModel.
 *
 * Routes:
 *   GET  /club/federations                         — lista federacji + status
 *   GET  /club/federations/:code/edit              — formularz konfiguracji
 *   POST /club/federations/:code/save              — zapis (upsert encrypted)
 *   POST /club/federations/:code/test              — test połączenia (adapter::testConnection)
 *   POST /club/federations/:code/toggle            — aktywacja/dezaktywacja
 *   POST /club/federations/:code/export-member     — manualny single member export
 *
 * Tylko admin/zarząd klubu — credentials API są wrażliwe.
 */
class ClubFederationController extends BaseController
{
    use ValidatesRequest;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    public function index(): void
    {
        $configured = (new ClubFederationCredentialModel())->listForClub();

        $existingByCode = [];
        foreach ($configured as $row) {
            $existingByCode[$row['federation_code']] = $row;
        }

        $this->render('club_federations/index', [
            'title'      => 'Integracje z federacjami sportowymi',
            'supported'  => FederationExporterFactory::supportedCodes(),
            'existing'   => $existingByCode,
        ]);
    }

    public function edit(string $code): void
    {
        $code = $this->normalizeCode($code);
        $config = (new ClubFederationCredentialModel())->findByFederation($code);
        $supported = FederationExporterFactory::supportedCodes();

        $this->render('club_federations/edit', [
            'title'  => 'Konfiguracja federacji: ' . $code,
            'code'   => $code,
            'label'  => $supported[$code] ?? $code,
            'config' => $config,
            'isManual' => !array_key_exists($code, $supported),
        ]);
    }

    public function save(string $code): void
    {
        Csrf::verify();
        $code = $this->normalizeCode($code);
        $back = 'club/federations';

        $data = [
            'is_sandbox'      => isset($_POST['is_sandbox']) ? 1 : 0,
            'is_active'       => isset($_POST['is_active'])  ? 1 : 0,
            'organization_id' => $this->validateOptionalString($_POST['organization_id'] ?? null, 60, $back),
            'notes'           => $this->validateOptionalString($_POST['notes'] ?? null, 500, $back),
            'api_username'    => trim($_POST['api_username'] ?? ''),
            'api_password'    => trim($_POST['api_password'] ?? ''),
            'api_token'       => trim($_POST['api_token']    ?? ''),
        ];

        (new ClubFederationCredentialModel())->upsert($code, $data);

        Session::flash('success', "Konfiguracja {$code} zapisana.");
        $this->redirect($back);
    }

    /** Test połączenia — wywołuje adapter::testConnection() i pokazuje wynik. */
    public function testConnection(string $code): void
    {
        Csrf::verify();
        $code   = $this->normalizeCode($code);
        $config = (new ClubFederationCredentialModel())->findByFederation($code);
        if (!$config) {
            $this->json(['success' => false, 'error' => 'federation_not_configured'], 404);
        }

        $exporter = FederationExporterFactory::forCode($code, $config);
        if (!$exporter) {
            $this->json(['success' => false, 'error' => 'no_adapter'], 500);
        }

        try {
            $result = $exporter->testConnection();
            $this->json([
                'success' => (bool)($result['ok'] ?? false),
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'error'   => 'exception',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleActive(string $code): void
    {
        Csrf::verify();
        $code   = $this->normalizeCode($code);
        $model  = new ClubFederationCredentialModel();
        $config = $model->findByFederation($code);
        if (!$config) {
            Session::flash('error', 'Brak konfiguracji.');
            $this->redirect('club/federations');
        }
        $newVal = empty($config['is_active']) ? 1 : 0;
        $model->update((int)$config['id'], ['is_active' => $newVal]);
        Session::flash('success', $newVal ? 'Integracja aktywowana.' : 'Integracja dezaktywowana.');
        $this->redirect('club/federations');
    }

    /**
     * Manualny eksport pojedynczego członka — testowanie + ad-hoc registracja.
     * POST param: member_id
     */
    public function exportMember(string $code): void
    {
        Csrf::verify();
        $code     = $this->normalizeCode($code);
        $memberId = (int)($_POST['member_id'] ?? 0);

        if ($memberId <= 0) {
            Session::flash('error', 'Brak member_id.');
            $this->redirect('club/federations');
        }

        $member = (new MemberModel())->findById($memberId);
        if (!$member) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('club/federations');
        }

        $payload = MemberPayload::fromMemberRow($member);

        $config = (new ClubFederationCredentialModel())->findByFederation($code);
        $exporter = FederationExporterFactory::forCode($code, $config ?? []);
        if (!$exporter) {
            Session::flash('error', "Brak adaptera dla federacji {$code}.");
            $this->redirect('club/federations');
        }

        $log   = new FederationExportLogModel();
        $logId = $log->logQueued(
            clubId:         $this->currentClub(),
            federationCode: $code,
            memberId:       $memberId,
            operation:      'register',
            requestPayload: $payload->toArray(),
            triggeredBy:    Auth::id() ? (int)Auth::id() : null,
        );

        try {
            $result = $exporter->exportMember($payload);
            if ($result->ok) {
                $log->markSuccess($logId, $result->rawResponse);
                Session::flash('success', "Eksport zawodnika do {$code}: OK. " . $result->message);
            } else {
                $log->markFailed($logId, $result->message ?: 'failed', $result->rawResponse);
                Session::flash('error', "Eksport do {$code} nieudany: " . $result->message);
            }
        } catch (FederationException $e) {
            $log->markFailed($logId, $e->getMessage());
            Session::flash('error', "Błąd eksportu do {$code}: " . $e->getMessage());
        } catch (\Throwable $e) {
            $log->markFailed($logId, 'Unexpected: ' . $e->getMessage());
            Session::flash('error', "Nieoczekiwany błąd eksportu: " . $e->getMessage());
        }

        $this->redirect('club/federations');
    }

    /** Normalizuj kod federacji (case-insensitive route param). */
    private function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        // Pozwól specyficzne case'y "PZKosz" jako rozpoznawany kod
        $supported = FederationExporterFactory::supportedCodes();
        foreach ($supported as $supportedCode => $_) {
            if (strtoupper($supportedCode) === $code) {
                return $supportedCode;
            }
        }
        return $code;
    }
}
