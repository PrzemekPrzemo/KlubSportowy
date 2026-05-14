<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\Shipping\InPostAdapter;
use App\Helpers\Shipping\ShipmentException;
use App\Helpers\ValidatesRequest;
use App\Models\ClubShippingProviderModel;
use App\Models\ShipmentModel;

/**
 * Per-klub konfiguracja providera wysyłki InPost (Paczkomaty + Kurier).
 *
 * Każdy klub trzyma WŁASNY token ShipX + organization_id (umowa handlowa
 * indywidualna). Wzorzec 1:1 z ClubGatewayController.
 *
 * Routes:
 *   GET  /club/shipping         — status integracji + lista ostatnich przesyłek
 *   GET  /club/shipping/edit    — formularz konfiguracji
 *   POST /club/shipping/save    — zapis (upsert encrypted)
 *   POST /club/shipping/test    — test połączenia (sandbox API ping)
 *   POST /club/shipping/toggle  — aktywacja/dezaktywacja
 *
 * Tylko zarząd klubu / admin — wrażliwe API credentials.
 */
class ClubShippingController extends BaseController
{
    use ValidatesRequest;

    private const PROVIDER = 'inpost';

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    public function index(): void
    {
        $config = (new ClubShippingProviderModel())->findByProvider(self::PROVIDER);
        $shipments = (new ShipmentModel())->recentForClub(20);

        $this->render('club_shipping/index', [
            'title'     => 'Wysyłka InPost',
            'config'    => $config,
            'shipments' => $shipments,
        ]);
    }

    public function edit(): void
    {
        $config = (new ClubShippingProviderModel())->findByProvider(self::PROVIDER);

        $this->render('club_shipping/edit', [
            'title'    => 'Konfiguracja: InPost',
            'config'   => $config,
            'sizes'    => ClubShippingProviderModel::$SIZES,
            'services' => ClubShippingProviderModel::$SERVICES,
        ]);
    }

    public function save(): void
    {
        Csrf::verify();
        $back = 'club/shipping/edit';

        $defaultSize = $_POST['default_size'] ?? 'A';
        if (!array_key_exists($defaultSize, ClubShippingProviderModel::$SIZES)) {
            $defaultSize = 'A';
        }
        $defaultService = $_POST['default_service'] ?? 'inpost_locker_standard';
        if (!array_key_exists($defaultService, ClubShippingProviderModel::$SERVICES)) {
            $defaultService = 'inpost_locker_standard';
        }

        $data = [
            'is_sandbox'               => isset($_POST['is_sandbox']) ? 1 : 0,
            // wrażliwe — pójdą przez Encryption w modelu (logical → _enc):
            'organization_id'          => trim((string)($_POST['organization_id'] ?? '')),
            'api_token'                => trim((string)($_POST['api_token']       ?? '')),
            'default_size'             => $defaultSize,
            'default_service'          => $defaultService,
            'sender_name'              => $this->validateOptionalString($_POST['sender_name']              ?? null, 120, $back),
            'sender_email'             => $this->validateOptionalString($_POST['sender_email']             ?? null, 120, $back),
            'sender_phone'             => $this->validateOptionalString($_POST['sender_phone']             ?? null, 20,  $back),
            'sender_address_street'    => $this->validateOptionalString($_POST['sender_address_street']    ?? null, 120, $back),
            'sender_address_building'  => $this->validateOptionalString($_POST['sender_address_building']  ?? null, 20,  $back),
            'sender_address_city'      => $this->validateOptionalString($_POST['sender_address_city']      ?? null, 80,  $back),
            'sender_address_post_code' => $this->validateOptionalString($_POST['sender_address_post_code'] ?? null, 10,  $back),
            'is_active'                => isset($_POST['is_active']) ? 1 : 0,
        ];

        (new ClubShippingProviderModel())->upsert(self::PROVIDER, $data);

        Session::flash('success', 'Konfiguracja InPost zapisana.');
        $this->redirect('club/shipping');
    }

    /**
     * Test połączenia — sandbox/prod API ping przez /v1/points (publiczny endpoint,
     * ale wymaga prawidłowych headers).
     */
    public function testConnection(): void
    {
        Csrf::verify();
        $config = (new ClubShippingProviderModel())->findByProvider(self::PROVIDER);
        if (!$config) {
            $this->json(['success' => false, 'error' => 'shipping_not_configured'], 404);
        }
        if (empty($config['api_token']) || empty($config['organization_id'])) {
            $this->json(['success' => false, 'error' => 'missing_credentials']);
        }

        try {
            $adapter = new InPostAdapter($config);
            // /v1/points jest najlżejszy, sprawdza i auth i sieć
            $points = $adapter->listPaczkomats('00-001', 1);
            $this->json([
                'success'          => true,
                'message'          => 'Połączenie OK. ' . count($points) . ' paczkomat(ów) znalezionych dla testowego post code.',
                'sandbox'          => (bool)$config['is_sandbox'],
                'organization_id'  => (string)$config['organization_id'],
            ]);
        } catch (ShipmentException $e) {
            $this->json([
                'success' => false,
                'error'   => 'api_error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function toggleActive(): void
    {
        Csrf::verify();
        $model  = new ClubShippingProviderModel();
        $config = $model->findByProvider(self::PROVIDER);
        if (!$config) {
            Session::flash('error', 'Brak konfiguracji InPost.');
            $this->redirect('club/shipping');
        }
        $newVal = empty($config['is_active']) ? 1 : 0;
        $model->update((int)$config['id'], ['is_active' => $newVal]);
        Session::flash('success', $newVal ? 'InPost aktywowany.' : 'InPost dezaktywowany.');
        $this->redirect('club/shipping');
    }
}
