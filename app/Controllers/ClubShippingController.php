<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Feature;
use App\Helpers\Session;
use App\Helpers\Shipping\InPostAdapter;
use App\Helpers\Shipping\ShipmentException;
use App\Helpers\Shipping\ShipmentRequest;
use App\Helpers\ValidatesRequest;
use App\Models\ClubShippingProviderModel;
use App\Models\MemberModel;
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

    /**
     * Formularz tworzenia przesylki — pre-fill z adresu czlonka (jesli ?member_id=N).
     *
     * Gate: feature flag `inpost_shipping` + aktywna konfiguracja InPost klubu.
     */
    public function create(): void
    {
        Feature::requireEnabled('inpost_shipping');

        $config = (new ClubShippingProviderModel())->activeForClub();
        if (!$config) {
            Session::flash('error',
                'Skonfiguruj i aktywuj InPost zanim nadasz przesylke.');
            $this->redirect('club/shipping');
        }

        $memberId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
        $member   = $memberId > 0 ? (new MemberModel())->findById($memberId) : null;

        $this->render('club_shipping/create', [
            'title'    => 'Nowa przesylka InPost',
            'config'   => $config,
            'member'   => $member,
            'sizes'    => ClubShippingProviderModel::$SIZES,
            'services' => ClubShippingProviderModel::$SERVICES,
        ]);
    }

    /**
     * POST /club/shipping/create — utworz przesylke przez InPost ShipX.
     *
     * Sekwencja:
     *  1. CSRF + feature flag check
     *  2. Pobierz aktywna konfiguracje (decrypted creds)
     *  3. Zwaliduj minimalne pola (recipient_*, size, service, target_locker_id dla paczkomatu)
     *  4. Zbuduj ShipmentRequest DTO
     *  5. InPostAdapter::createShipment()
     *  6. INSERT do shipments
     *  7. Flash + redirect na liste
     */
    public function storeShipment(): void
    {
        Csrf::verify();
        Feature::requireEnabled('inpost_shipping');

        $config = (new ClubShippingProviderModel())->activeForClub();
        if (!$config) {
            Session::flash('error', 'Brak aktywnej konfiguracji InPost.');
            $this->redirect('club/shipping');
        }

        $memberId = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
        $member   = $memberId > 0 ? (new MemberModel())->findById($memberId) : null;

        $size = $_POST['size'] ?? ($config['default_size'] ?? 'A');
        if (!array_key_exists($size, ClubShippingProviderModel::$SIZES)) {
            $size = 'A';
        }
        $service = $_POST['service'] ?? ($config['default_service'] ?? 'inpost_locker_standard');
        if (!array_key_exists($service, ClubShippingProviderModel::$SERVICES)) {
            $service = 'inpost_locker_standard';
        }
        $isLocker = str_contains($service, 'locker');

        $recipientName  = trim((string)($_POST['recipient_name']  ?? ''));
        $recipientEmail = trim((string)($_POST['recipient_email'] ?? ''));
        $recipientPhone = trim((string)($_POST['recipient_phone'] ?? ''));
        $targetLocker   = trim((string)($_POST['target_locker_id'] ?? ''));
        $street         = trim((string)($_POST['recipient_street']    ?? ''));
        $building       = trim((string)($_POST['recipient_building']  ?? ''));
        $city           = trim((string)($_POST['recipient_city']      ?? ''));
        $postCode       = trim((string)($_POST['recipient_post_code'] ?? ''));
        $note           = trim((string)($_POST['internal_note'] ?? ''));

        if ($recipientName === '' || $recipientEmail === '' || $recipientPhone === '') {
            Session::flash('error', 'Wypelnij dane odbiorcy (imie/nazwisko, email, telefon).');
            $this->redirect('club/shipping/create' . ($memberId > 0 ? '?member_id=' . $memberId : ''));
        }
        if ($isLocker && $targetLocker === '') {
            Session::flash('error', 'Wybierz docelowy paczkomat (target_locker_id).');
            $this->redirect('club/shipping/create' . ($memberId > 0 ? '?member_id=' . $memberId : ''));
        }
        if (!$isLocker && ($street === '' || $city === '' || $postCode === '')) {
            Session::flash('error', 'Dla kuriera wymagany jest pelny adres odbiorcy.');
            $this->redirect('club/shipping/create' . ($memberId > 0 ? '?member_id=' . $memberId : ''));
        }

        $req = new ShipmentRequest(
            clubId:            (int)$this->currentClub(),
            recipientName:     $recipientName,
            recipientEmail:    $recipientEmail,
            recipientPhone:    $recipientPhone,
            size:              $size,
            service:           $service,
            targetLockerId:    $isLocker ? ($targetLocker !== '' ? $targetLocker : null) : null,
            recipientStreet:   $street    !== '' ? $street    : null,
            recipientBuilding: $building  !== '' ? $building  : null,
            recipientCity:     $city      !== '' ? $city      : null,
            recipientPostCode: $postCode  !== '' ? $postCode  : null,
            memberId:          $memberId > 0 ? $memberId : null,
            internalNote:      $note !== '' ? $note : null,
        );

        try {
            $adapter = new InPostAdapter($config);
            $result  = $adapter->createShipment($req);
        } catch (ShipmentException $e) {
            Session::flash('error', 'InPost: ' . $e->getMessage());
            $this->redirect('club/shipping/create' . ($memberId > 0 ? '?member_id=' . $memberId : ''));
        }

        (new ShipmentModel())->insert([
            'provider'         => 'inpost',
            'external_id'      => $result->externalId,
            'tracking_number'  => $result->trackingNumber,
            'label_url'        => $result->labelUrl,
            'recipient_name'   => $recipientName,
            'recipient_email'  => $recipientEmail,
            'recipient_phone'  => $recipientPhone,
            'target_locker_id' => $isLocker ? $targetLocker : null,
            'size'             => $size,
            'status'           => $result->status,
            'member_id'        => $memberId > 0 ? $memberId : null,
            'internal_note'    => $note !== '' ? $note : null,
        ]);

        Session::flash('success',
            'Przesylka utworzona (tracking: ' . ($result->trackingNumber ?? $result->externalId) . ').');
        $this->redirect('club/shipping/shipments');
    }

    /**
     * Lista wszystkich przesylek klubu — pelna (nie tylko top 20 jak index).
     */
    public function listShipments(): void
    {
        Feature::requireEnabled('inpost_shipping');

        $shipments = (new ShipmentModel())->recentForClub(500);
        $this->render('club_shipping/shipments', [
            'title'     => 'Przesylki InPost',
            'shipments' => $shipments,
        ]);
    }

    /**
     * GET /club/shipping/label/:id — przekierowanie na InPost-owy URL etykiety PDF.
     *
     * InPost zwraca PDF bezposrednio z swojego API (z auth Bearer w nagłowku),
     * wiec nie mozemy po prostu zrobic 302. Pobieramy binarke przez cURL
     * i streamujemy do klienta.
     */
    public function downloadLabel(string $id): void
    {
        Feature::requireEnabled('inpost_shipping');

        $shipmentId = (int)$id;
        $clubId     = $this->currentClub();

        $stmt = \App\Helpers\Database::pdo()->prepare(
            "SELECT * FROM shipments WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$shipmentId, $clubId]);
        $shipment = $stmt->fetch();
        if (!$shipment || empty($shipment['external_id'])) {
            Session::flash('error', 'Przesylka nieznaleziona lub brak external_id.');
            $this->redirect('club/shipping/shipments');
        }

        $config = (new ClubShippingProviderModel())->activeForClub();
        if (!$config) {
            Session::flash('error', 'Brak aktywnej konfiguracji InPost.');
            $this->redirect('club/shipping/shipments');
        }

        $adapter = new InPostAdapter($config);
        $url     = $adapter->fetchLabel((string)$shipment['external_id']);

        // Pobierz PDF z InPost API z Bearer auth i streamuj
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $config['api_token'],
                'Accept: application/pdf',
            ],
        ]);
        $pdf  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($pdf === false || $code < 200 || $code >= 300) {
            Session::flash('error', 'InPost: nie udalo sie pobrac etykiety (HTTP ' . $code . ').');
            $this->redirect('club/shipping/shipments');
        }

        $filename = 'inpost-label-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$shipment['external_id']) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen((string)$pdf));
        echo $pdf;
        exit;
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
