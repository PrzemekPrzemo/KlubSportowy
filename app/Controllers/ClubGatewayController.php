<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\ClubPaymentGatewayModel;

/**
 * Faza P.5 — per-klub konfiguracja bramek płatności (Przelewy24, PayU,
 * Stripe, Tpay).
 *
 * Każdy klub ma WŁASNE API credentials — pozwala SaaS-owi rozliczać
 * każdą organizację z jej własnym kontem bankowym/merchantem (zamiast
 * jednego globalnego konta ClubDesk).
 *
 * Pola wrażliwe (api_key, api_secret, crc_key, webhook_secret) szyfrowane
 * AES-256-GCM przez App\Helpers\Encryption — zarządzane w
 * ClubPaymentGatewayModel.
 *
 * Routes:
 *   GET  /club/gateways                    — lista skonfigurowanych
 *   GET  /club/gateways/:provider/edit     — formularz konfiguracji
 *   POST /club/gateways/:provider/save     — zapis (upsert encrypted)
 *   POST /club/gateways/:provider/test     — test połączenia (dry call API)
 *   POST /club/gateways/:provider/toggle   — aktywacja/dezaktywacja
 *   POST /club/gateways/:provider/delete   — usun konfiguracje
 *
 * Tylko admin klubu może modyfikować — chronione requireRole('zarzad').
 */
class ClubGatewayController extends BaseController
{
    use ValidatesRequest;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        // Tylko zarząd klubu — credentials API to wrażliwe dane
        $this->requireRole(['zarzad', 'admin']);
    }

    public function index(): void
    {
        $configured = (new ClubPaymentGatewayModel())->listForClub();

        // Pre-fill providers list — pokazujemy też te niesetupowane
        $allProviders = ClubPaymentGatewayModel::$PROVIDERS;
        $existingByProvider = [];
        foreach ($configured as $g) {
            $existingByProvider[$g['provider']] = $g;
        }

        $this->render('club_gateways/index', [
            'title'     => 'Bramki płatności',
            'providers' => $allProviders,
            'existing'  => $existingByProvider,
        ]);
    }

    public function edit(string $provider): void
    {
        if (!array_key_exists($provider, ClubPaymentGatewayModel::$PROVIDERS)) {
            Session::flash('error', 'Nieznany provider.');
            $this->redirect('club/gateways');
        }
        $config = (new ClubPaymentGatewayModel())->findByProvider($provider);

        $this->render('club_gateways/edit', [
            'title'    => 'Konfiguracja: ' . ClubPaymentGatewayModel::$PROVIDERS[$provider],
            'provider' => $provider,
            'providerLabel' => ClubPaymentGatewayModel::$PROVIDERS[$provider],
            'config'   => $config,
        ]);
    }

    public function save(string $provider): void
    {
        Csrf::verify();
        $back = 'club/gateways';

        if (!array_key_exists($provider, ClubPaymentGatewayModel::$PROVIDERS)) {
            Session::flash('error', 'Nieznany provider.');
            $this->redirect($back);
        }

        $data = [
            'is_sandbox'     => isset($_POST['is_sandbox']) ? 1 : 0,
            'merchant_id'    => $this->validateOptionalString($_POST['merchant_id'] ?? null, 120, $back),
            'api_key'        => trim($_POST['api_key']        ?? ''),
            'api_secret'     => trim($_POST['api_secret']     ?? ''),
            'crc_key'        => trim($_POST['crc_key']        ?? ''),
            'webhook_secret' => trim($_POST['webhook_secret'] ?? ''),
            'return_url'     => $this->validateOptionalString($_POST['return_url']     ?? null, 255, $back),
            'notify_url'     => $this->validateOptionalString($_POST['notify_url']     ?? null, 255, $back),
            'currency'       => preg_match('/^[A-Z]{3}$/', $_POST['currency'] ?? '') ? $_POST['currency'] : 'PLN',
            'notes'          => $this->validateOptionalString($_POST['notes'] ?? null, 5000, $back),
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ];

        // upsert — model szyfruje wrażliwe pola, jeśli puste zostawia stare zaszyfrowane
        (new ClubPaymentGatewayModel())->upsert($provider, $data);

        Session::flash('success', "Konfiguracja {$provider} zapisana.");
        $this->redirect($back);
    }

    /**
     * Test connection: weryfikuje że API key + secret są prawidłowe.
     * Zwraca JSON z wynikiem (uzywane przez AJAX z UI).
     */
    public function testConnection(string $provider): void
    {
        Csrf::verify();
        $config = (new ClubPaymentGatewayModel())->findByProvider($provider);
        if (!$config) {
            $this->json(['success' => false, 'error' => 'gateway_not_configured'], 404);
        }

        // Provider-specific test — minimalna implementacja przez sanity check
        // (faktyczny call do API gateway w przyszłości — wymaga SDK).
        $hasCreds = !empty($config['api_key']) && !empty($config['merchant_id']);
        if (!$hasCreds) {
            $this->json(['success' => false, 'error' => 'missing_credentials']);
        }

        // TODO: real API ping per provider
        // Przelewy24: GET /api/v1/testAccess (Bearer token)
        // PayU: GET /api/v2_1/orders/{id}
        // Stripe: list charges with limit=1
        // Tpay: GET /transactions
        $this->json([
            'success'  => true,
            'message'  => 'Konfiguracja kompletna (sanity check). Pełny test API w przyszłości.',
            'sandbox'  => (bool)$config['is_sandbox'],
            'merchant' => $config['merchant_id'],
        ]);
    }

    public function toggleActive(string $provider): void
    {
        Csrf::verify();
        $model = new ClubPaymentGatewayModel();
        $config = $model->findByProvider($provider);
        if (!$config) {
            Session::flash('error', 'Brak konfiguracji.');
            $this->redirect('club/gateways');
        }
        $newVal = empty($config['is_active']) ? 1 : 0;
        $model->update((int)$config['id'], ['is_active' => $newVal]);
        Session::flash('success', $newVal ? 'Bramka aktywowana.' : 'Bramka dezaktywowana.');
        $this->redirect('club/gateways');
    }

    public function delete(string $provider): void
    {
        Csrf::verify();
        $model = new ClubPaymentGatewayModel();
        $config = $model->findByProvider($provider);
        if ($config) {
            $model->delete((int)$config['id']);
            Session::flash('success', 'Konfiguracja usunięta.');
        }
        $this->redirect('club/gateways');
    }
}
