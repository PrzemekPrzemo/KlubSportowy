<?php

namespace App\Helpers\Gateway;

/**
 * PayU adapter — REST API v2.1
 *
 * Protocol:
 *   1. POST /api/v2_1/orders          → tworzy zamówienie + redirectUri
 *   2. Redirect user do redirectUri (PayU SecureForm)
 *   3. PayU webhook → POST /notify z statusem (HMAC SHA-256 signature)
 *   4. GET /api/v2_1/orders/{id}      → status check (reconciliation)
 *
 * Authentication: OAuth 2.0 Client Credentials Grant
 *   - POST /pl/standard/user/oauth/authorize z grant_type=client_credentials
 *   - Body: client_id={merchant_id} & client_secret={api_secret}
 *   - Token TTL ~12h — cache w pamięci procesu
 *
 * Konfiguracja (z club_payment_gateways z P.5):
 *   - merchant_id (POS ID)
 *   - api_key      → client_id (tożsamy z POS ID lub osobny)
 *   - api_secret   → client_secret
 *   - webhook_secret → MD5 second key (do weryfikacji notifications)
 *
 * @link https://developers.payu.com/en/restapi.html
 */
class PayUAdapter implements GatewayAdapterInterface
{
    private const SANDBOX_HOST = 'https://secure.snd.payu.com';
    private const PROD_HOST    = 'https://secure.payu.com';

    private ?string $cachedToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(
        private readonly array $config,
    ) {
    }

    public function providerKey(): string
    {
        return 'payu';
    }

    public function createCheckout(CheckoutRequest $req): CheckoutResult
    {
        $this->assertConfigured();

        $token = $this->getOAuthToken();
        $extOrderId = substr($req->internalReference . '_' . hrtime(true), 0, 100);

        $payload = [
            'notifyUrl'      => $req->notifyUrl,
            'continueUrl'    => $req->successUrl,
            'customerIp'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'merchantPosId'  => (string)$this->config['merchant_id'],
            'description'    => mb_substr($req->description, 0, 1024),
            'currencyCode'   => strtoupper($req->currency ?: 'PLN'),
            'totalAmount'    => (string)(int)round($req->amount * 100),
            'extOrderId'     => $extOrderId,
            'buyer'          => array_filter([
                'email' => $req->customerEmail,
                'language' => 'pl',
            ]),
            'products'       => [[
                'name'      => mb_substr($req->description, 0, 200),
                'unitPrice' => (string)(int)round($req->amount * 100),
                'quantity'  => '1',
            ]],
        ];

        $resp = $this->httpJson('POST', '/api/v2_1/orders', $payload, $token, false /* don't follow redirect */);

        // PayU zwraca 302 z 'orderId' i 'redirectUri' (lub 200 z JSON)
        $orderId = $resp['orderId'] ?? null;
        $redirectUri = $resp['redirectUri'] ?? null;

        if (!$orderId || !$redirectUri) {
            throw new GatewayException(
                'PayU order creation failed: ' . json_encode($resp, JSON_UNESCAPED_UNICODE)
            );
        }

        return new CheckoutResult(
            redirectUrl: $redirectUri,
            externalId:  (string)$orderId,
            rawResponse: ['order_id' => $orderId, 'ext_order_id' => $extOrderId],
        );
    }

    public function verifyWebhook(string $rawPayload, array $headers): WebhookEvent
    {
        // PayU header: OpenPayu-Signature: sender=...;algorithm=MD5;signature=...
        $sigHeader = $headers['OpenPayu-Signature']
            ?? $headers['openpayu-signature']
            ?? $headers['HTTP_OPENPAYU_SIGNATURE']
            ?? '';
        if ($sigHeader === '') {
            throw new GatewayException('PayU webhook: missing OpenPayu-Signature header');
        }

        $parts = [];
        foreach (explode(';', $sigHeader) as $kv) {
            [$k, $v] = array_pad(explode('=', $kv, 2), 2, '');
            $parts[trim($k)] = trim($v);
        }
        $sigValue = $parts['signature'] ?? '';
        $algorithm = strtoupper($parts['algorithm'] ?? 'MD5');

        if ($sigValue === '') {
            throw new GatewayException('PayU webhook: signature value missing');
        }

        $secret = (string)($this->config['webhook_secret'] ?? '');
        if ($secret === '') {
            throw new GatewayException('PayU webhook_secret not configured');
        }

        // Weryfikacja: signature == hash(algorithm, payload + secret)
        $expected = match ($algorithm) {
            'MD5'     => md5($rawPayload . $secret),
            'SHA-256' => hash('sha256', $rawPayload . $secret),
            default   => null,
        };
        if ($expected === null || !hash_equals($expected, $sigValue)) {
            throw new GatewayException('PayU webhook: signature mismatch');
        }

        $data = json_decode($rawPayload, true);
        if (!is_array($data) || empty($data['order'])) {
            throw new GatewayException('PayU webhook: invalid payload structure');
        }

        $order = $data['order'];
        $payuStatus = strtoupper((string)($order['status'] ?? ''));
        $status = match ($payuStatus) {
            'COMPLETED'         => WebhookEvent::STATUS_PAID,
            'CANCELED', 'REJECTED' => WebhookEvent::STATUS_CANCELLED,
            'PENDING', 'WAITING_FOR_CONFIRMATION', 'NEW' => WebhookEvent::STATUS_PENDING,
            default             => WebhookEvent::STATUS_PENDING,
        };

        $totalAmount = isset($order['totalAmount']) ? ((int)$order['totalAmount']) / 100 : null;

        return new WebhookEvent(
            externalId:        (string)($order['orderId'] ?? ''),
            status:            $status,
            amount:            $totalAmount,
            currency:          strtoupper($order['currencyCode'] ?? 'PLN'),
            internalReference: (string)($order['extOrderId'] ?? ''),
            rawPayload:        $data,
        );
    }

    public function fetchStatus(string $externalId): TransactionStatus
    {
        $this->assertConfigured();
        $token = $this->getOAuthToken();

        $resp = $this->httpJson('GET', '/api/v2_1/orders/' . urlencode($externalId), null, $token);

        $orders = $resp['orders'] ?? [];
        $order = $orders[0] ?? null;
        if (!$order) {
            throw new GatewayException('PayU fetchStatus: order not found');
        }

        $status = match (strtoupper($order['status'] ?? '')) {
            'COMPLETED'                                => WebhookEvent::STATUS_PAID,
            'CANCELED', 'REJECTED'                     => WebhookEvent::STATUS_CANCELLED,
            'PENDING', 'WAITING_FOR_CONFIRMATION', 'NEW' => WebhookEvent::STATUS_PENDING,
            default                                    => WebhookEvent::STATUS_PENDING,
        };

        return new TransactionStatus(
            externalId:  $externalId,
            status:      $status,
            amount:      isset($order['totalAmount']) ? ((int)$order['totalAmount']) / 100 : null,
            currency:    strtoupper($order['currencyCode'] ?? 'PLN'),
            rawResponse: $order,
        );
    }

    /**
     * OAuth 2.0 Client Credentials Grant — token TTL ~12h, cache in-memory.
     */
    private function getOAuthToken(): string
    {
        $now = time();
        if ($this->cachedToken && $this->tokenExpiresAt !== null && $this->tokenExpiresAt > $now + 60) {
            return $this->cachedToken;
        }

        $url = $this->host() . '/pl/standard/user/oauth/authorize';
        $body = http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => (string)$this->config['merchant_id'],
            'client_secret' => (string)$this->config['api_secret'],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $respBody = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($respBody === false || $err !== '' || $code < 200 || $code >= 300) {
            throw new GatewayException("PayU OAuth failed (HTTP {$code}): {$err}");
        }

        $data = json_decode($respBody, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new GatewayException('PayU OAuth: invalid response');
        }

        $this->cachedToken    = (string)$data['access_token'];
        $this->tokenExpiresAt = $now + ((int)($data['expires_in'] ?? 43200));
        return $this->cachedToken;
    }

    private function host(): string
    {
        return !empty($this->config['is_sandbox']) ? self::SANDBOX_HOST : self::PROD_HOST;
    }

    private function assertConfigured(): void
    {
        foreach (['merchant_id', 'api_secret'] as $f) {
            if (empty($this->config[$f])) {
                throw new GatewayException("PayU missing config: {$f}");
            }
        }
    }

    /**
     * @param string|null $bearerToken — null gdy nie wymaga auth (np. OAuth endpoint)
     */
    private function httpJson(string $method, string $path, ?array $payload, ?string $bearerToken, bool $followRedirect = true): array
    {
        $url = $this->host() . $path;
        $ch = curl_init($url);

        $headers = ['Accept: application/json'];
        if ($bearerToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $bearerToken;
        }
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
            // PayU /orders zwraca 302 z Location: redirectUri — nie podążamy,
            // czytamy header'y i body z 302 dla orderId/redirectUri w JSON body.
            CURLOPT_FOLLOWLOCATION => $followRedirect,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            throw new GatewayException("PayU cURL error: {$err}");
        }
        // PayU createOrder zwraca 302 jako sukces (z body JSON)
        if ($code < 200 || ($code >= 400)) {
            throw new GatewayException("PayU HTTP {$code}: " . substr((string)$body, 0, 500));
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            throw new GatewayException('PayU: invalid JSON response');
        }
        return $data;
    }
}
