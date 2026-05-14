<?php

namespace App\Helpers\Shipping;

/**
 * InPost ShipX API v1 adapter — paczkomaty + kurier.
 *
 * Endpointy:
 *   POST /v1/organizations/{org_id}/shipments  — utworzenie przesyłki
 *   GET  /v1/shipments/{id}                    — status
 *   GET  /v1/shipments/{id}/label              — etykieta PDF
 *   GET  /v1/points?type=parcel_locker&...     — wyszukiwarka paczkomatów
 *
 * Auth: Bearer token (organization-scoped, generowany w panelu manager.paczkomaty.pl).
 *
 * Bez external SDK — InPost SDK na composer jest słabo utrzymywany,
 * raw cURL daje pełną kontrolę nad timeout/retry/błędami.
 *
 * @link https://docs.shipx.pl
 */
class InPostAdapter implements ShippingAdapterInterface
{
    private const SANDBOX_HOST = 'https://sandbox-api-shipx-pl.easypack24.net';
    private const PROD_HOST    = 'https://api-shipx-pl.easypack24.net';

    public function __construct(
        /** Decrypted config: organization_id, api_token, is_sandbox, sender_* */
        private readonly array $config,
    ) {
    }

    public function providerKey(): string
    {
        return 'inpost';
    }

    public function createShipment(ShipmentRequest $req): ShipmentResult
    {
        $this->assertConfigured();

        $service = $req->service ?: 'inpost_locker_standard';
        $isLocker = str_contains($service, 'locker');

        // Adres odbiorcy: dla paczkomatu InPost wymaga targetLockerId + dane
        // kontaktowe (email/telefon na powiadomienia). Dla kuriera — pełny adres.
        $receiver = [
            'name'          => $req->recipientName,
            'email'         => $req->recipientEmail,
            'phone'         => $this->normalizePhone($req->recipientPhone),
        ];
        if (!$isLocker) {
            $receiver['address'] = [
                'street'         => $req->recipientStreet ?? '',
                'building_number'=> $req->recipientBuilding ?? '',
                'city'           => $req->recipientCity ?? '',
                'post_code'      => $req->recipientPostCode ?? '',
                'country_code'   => 'PL',
            ];
        }

        $payload = [
            'receiver'         => $receiver,
            'sender'           => $this->buildSenderPayload(),
            'parcels'          => [[
                'template' => strtolower($req->size ?: 'a'), // small=a, medium=b, large=c
            ]],
            'service'          => $service,
            'reference'        => $req->internalNote
                ? mb_substr($req->internalNote, 0, 100)
                : 'club_' . $req->clubId,
        ];

        if ($isLocker && $req->targetLockerId) {
            $payload['custom_attributes'] = [
                'target_point' => $req->targetLockerId,
            ];
        }

        if ($req->weightKg !== null) {
            // InPost przyjmuje dimensions/weight w custom_attributes dla "non-standard"
            $payload['parcels'][0]['dimensions'] = [
                'length' => 64, 'width' => 38, 'height' => 8, 'unit' => 'mm', // template-default
            ];
            $payload['parcels'][0]['weight'] = ['amount' => $req->weightKg, 'unit' => 'kg'];
        }

        $orgId = (string)$this->config['organization_id'];
        $resp = $this->http('POST', '/v1/organizations/' . urlencode($orgId) . '/shipments', $payload);

        $externalId  = isset($resp['id']) ? (string)$resp['id'] : '';
        $tracking    = $resp['tracking_number'] ?? null;
        $labelUrl    = $resp['label_url'] ?? null;
        $status      = $resp['status'] ?? 'created';

        if ($externalId === '') {
            throw new ShipmentException(
                'InPost createShipment: missing id in response: ' . json_encode($resp, JSON_UNESCAPED_UNICODE)
            );
        }

        return new ShipmentResult(
            externalId:     $externalId,
            trackingNumber: $tracking,
            labelUrl:       $labelUrl,
            status:         (string)$status,
            rawResponse:    $resp,
        );
    }

    public function fetchLabel(string $externalId): string
    {
        $this->assertConfigured();
        // InPost zwraca PDF (Content-Type: application/pdf), nie JSON.
        // Zwracamy URL do download via API (nie cache'ujemy binarki w DB).
        $host = $this->host();
        return $host . '/v1/shipments/' . urlencode($externalId) . '/label?format=pdf&type=normal';
    }

    public function trackShipment(string $externalId): array
    {
        $this->assertConfigured();
        $resp = $this->http('GET', '/v1/shipments/' . urlencode($externalId), null);

        return [
            'status'  => (string)($resp['status'] ?? 'unknown'),
            'history' => $resp['tracking_details'] ?? [],
            'raw'     => $resp,
        ];
    }

    public function listPaczkomats(string $postCode, int $limit = 20): array
    {
        $this->assertConfigured();
        $postCode = preg_replace('/[^0-9\-]/', '', $postCode);
        $limit = max(1, min(100, $limit));

        $resp = $this->http(
            'GET',
            '/v1/points?type=parcel_locker&relative_post_code=' . urlencode($postCode)
                . '&per_page=' . $limit,
            null,
            authRequired: false, // /v1/points jest publiczny, ale wysłanie tokenu nie szkodzi
        );

        $items = $resp['items'] ?? [];
        $out = [];
        foreach ($items as $p) {
            $out[] = [
                'name'      => (string)($p['name'] ?? ''),
                'address'   => (string)($p['address']['line1'] ?? ''),
                'city'      => (string)($p['address_details']['city'] ?? ($p['address']['line2'] ?? '')),
                'post_code' => (string)($p['address_details']['post_code'] ?? ''),
            ];
        }
        return $out;
    }

    private function buildSenderPayload(): array
    {
        return [
            'name'  => (string)($this->config['sender_name'] ?? ''),
            'email' => (string)($this->config['sender_email'] ?? ''),
            'phone' => $this->normalizePhone((string)($this->config['sender_phone'] ?? '')),
            'address' => [
                'street'          => (string)($this->config['sender_address_street'] ?? ''),
                'building_number' => (string)($this->config['sender_address_building'] ?? ''),
                'city'            => (string)($this->config['sender_address_city'] ?? ''),
                'post_code'       => (string)($this->config['sender_address_post_code'] ?? ''),
                'country_code'    => 'PL',
            ],
        ];
    }

    private function normalizePhone(string $phone): string
    {
        // InPost oczekuje 9-cyfrowego numeru PL (bez +48 / spacji).
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) >= 11 && str_starts_with($digits, '48')) {
            $digits = substr($digits, 2);
        }
        return substr($digits, -9);
    }

    private function host(): string
    {
        return !empty($this->config['is_sandbox']) ? self::SANDBOX_HOST : self::PROD_HOST;
    }

    private function assertConfigured(): void
    {
        foreach (['organization_id', 'api_token'] as $f) {
            if (empty($this->config[$f])) {
                throw new ShipmentException("InPost missing config: {$f}");
            }
        }
    }

    /**
     * cURL helper analogiczny do Przelewy24Adapter::http().
     */
    private function http(string $method, string $path, ?array $payload, bool $authRequired = true): array
    {
        $url = $this->host() . $path;
        $ch  = curl_init($url);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($authRequired) {
            $headers[] = 'Authorization: Bearer ' . $this->config['api_token'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($payload !== null) {
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            throw new ShipmentException("InPost cURL error: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new ShipmentException("InPost HTTP {$code}: " . substr((string)$body, 0, 500));
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            throw new ShipmentException('InPost: invalid JSON response');
        }
        return $data;
    }
}
