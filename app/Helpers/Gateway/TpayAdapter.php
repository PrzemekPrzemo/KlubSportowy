<?php

namespace App\Helpers\Gateway;

/**
 * Tpay adapter — Tpay Open API v1 (REST).
 *
 * Protocol:
 *   1. POST /transactions               → tworzy transakcję, zwraca url do payment page
 *   2. Redirect user do transactionPaymentUrl
 *   3. Webhook (notification URL) → POST z polami md5
 *   4. GET /transactions/{id}           → status check
 *
 * Authentication: HTTP Basic (api_key:api_secret) lub OAuth Bearer.
 *
 * Konfiguracja (z club_payment_gateways z P.5):
 *   - merchant_id    → PSP ID (numeric)
 *   - api_key        → API client_id
 *   - api_secret     → API client_secret
 *   - webhook_secret → security code (do MD5 webhook signatures)
 *
 * @link https://docs.tpay.com/v1/openapi/
 */
class TpayAdapter implements GatewayAdapterInterface
{
    private const SANDBOX_HOST = 'https://openapi.sandbox.tpay.com';
    private const PROD_HOST    = 'https://openapi.tpay.com';

    private ?string $cachedToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(
        private readonly array $config,
    ) {
    }

    public function providerKey(): string
    {
        return 'tpay';
    }

    public function createCheckout(CheckoutRequest $req): CheckoutResult
    {
        $this->assertConfigured();
        $token = $this->getOAuthToken();

        $extId = substr($req->internalReference . '_' . hrtime(true), 0, 100);

        $payload = [
            'amount'      => round($req->amount, 2),
            'description' => mb_substr($req->description, 0, 250),
            'hiddenDescription' => $extId,
            'lang'        => 'pl',
            'pay'         => [
                'groupId' => 0, // 0 = wszystkie metody (BLIK, karty, przelewy)
            ],
            'payer'       => array_filter([
                'email'   => $req->customerEmail,
                'name'    => 'Klient #' . $req->memberId,
            ]),
            'callbacks'   => [
                'payerUrls' => [
                    'success' => $req->successUrl,
                    'error'   => $req->cancelUrl,
                ],
                'notification' => [
                    'url'   => $req->notifyUrl,
                    'email' => null,
                ],
            ],
        ];

        $resp = $this->httpJson('POST', '/transactions', $payload, $token);

        $tid = $resp['transactionId'] ?? null;
        $url = $resp['transactionPaymentUrl'] ?? null;
        if (!$tid || !$url) {
            throw new GatewayException('Tpay create transaction failed: ' . json_encode($resp, JSON_UNESCAPED_UNICODE));
        }

        return new CheckoutResult(
            redirectUrl: $url,
            externalId:  (string)$tid,
            rawResponse: ['transaction_id' => $tid, 'ext_id' => $extId],
        );
    }

    public function verifyWebhook(string $rawPayload, array $headers): WebhookEvent
    {
        // Tpay legacy notification: form-encoded payload (nie JSON)
        // Pola: id, tr_id, tr_amount, tr_status, tr_crc, md5sum (signature)
        // md5sum = md5(id + tr_id + tr_amount + tr_crc + security_code)
        parse_str($rawPayload, $data);
        if (empty($data) || !is_array($data)) {
            // Fallback: spróbuj JSON (nowszy format)
            $data = json_decode($rawPayload, true);
        }
        if (!is_array($data)) {
            throw new GatewayException('Tpay webhook: invalid payload');
        }

        $required = ['id', 'tr_id', 'tr_amount', 'tr_crc', 'tr_status', 'md5sum'];
        foreach ($required as $f) {
            if (!isset($data[$f])) {
                throw new GatewayException("Tpay webhook: missing field '{$f}'");
            }
        }

        $secret = (string)($this->config['webhook_secret'] ?? '');
        if ($secret === '') {
            throw new GatewayException('Tpay webhook_secret not configured');
        }

        // md5sum = md5(id + tr_id + tr_amount + tr_crc + security_code)
        $expected = md5(
            $data['id'] . $data['tr_id'] . $data['tr_amount'] . $data['tr_crc'] . $secret
        );
        if (!hash_equals($expected, (string)$data['md5sum'])) {
            throw new GatewayException('Tpay webhook: signature mismatch');
        }

        $tpayStatus = strtoupper((string)$data['tr_status']);
        $status = match ($tpayStatus) {
            'TRUE', 'PAID'                  => WebhookEvent::STATUS_PAID,
            'CHARGEBACK', 'REFUND'          => WebhookEvent::STATUS_REFUNDED,
            'FALSE', 'FAIL', 'FAILURE'      => WebhookEvent::STATUS_FAILED,
            default                         => WebhookEvent::STATUS_PENDING,
        };

        return new WebhookEvent(
            externalId:        (string)$data['tr_id'],
            status:            $status,
            amount:            (float)$data['tr_amount'],
            currency:          'PLN', // Tpay legacy notify nie ma currency w body, default PLN
            internalReference: (string)($data['tr_crc'] ?? ''),
            rawPayload:        $data,
        );
    }

    public function fetchStatus(string $externalId): TransactionStatus
    {
        $this->assertConfigured();
        $token = $this->getOAuthToken();

        $resp = $this->httpJson('GET', '/transactions/' . urlencode($externalId), null, $token);

        $tpayStatus = strtolower((string)($resp['status'] ?? ''));
        $status = match ($tpayStatus) {
            'correct', 'paid' => WebhookEvent::STATUS_PAID,
            'chargeback'      => WebhookEvent::STATUS_REFUNDED,
            'failed', 'fail'  => WebhookEvent::STATUS_FAILED,
            default           => WebhookEvent::STATUS_PENDING,
        };

        return new TransactionStatus(
            externalId:  $externalId,
            status:      $status,
            amount:      isset($resp['amount']) ? (float)$resp['amount'] : null,
            currency:    'PLN',
            rawResponse: $resp,
        );
    }

    /**
     * OAuth 2.0 client_credentials. Tpay token TTL 7200s (2h).
     */
    private function getOAuthToken(): string
    {
        $now = time();
        if ($this->cachedToken && $this->tokenExpiresAt !== null && $this->tokenExpiresAt > $now + 60) {
            return $this->cachedToken;
        }

        $url = $this->host() . '/oauth/auth';
        $payload = [
            'client_id'     => (string)$this->config['api_key'],
            'client_secret' => (string)$this->config['api_secret'],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '' || $code < 200 || $code >= 300) {
            throw new GatewayException("Tpay OAuth failed (HTTP {$code}): {$err}");
        }
        $data = json_decode((string)$body, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new GatewayException('Tpay OAuth: invalid response');
        }

        $this->cachedToken    = (string)$data['access_token'];
        $this->tokenExpiresAt = $now + ((int)($data['expires_in'] ?? 7200));
        return $this->cachedToken;
    }

    private function host(): string
    {
        return !empty($this->config['is_sandbox']) ? self::SANDBOX_HOST : self::PROD_HOST;
    }

    private function assertConfigured(): void
    {
        foreach (['merchant_id', 'api_key', 'api_secret'] as $f) {
            if (empty($this->config[$f])) {
                throw new GatewayException("Tpay missing config: {$f}");
            }
        }
    }

    private function httpJson(string $method, string $path, ?array $payload, string $token): array
    {
        $url = $this->host() . $path;
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            throw new GatewayException("Tpay cURL error: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new GatewayException("Tpay HTTP {$code}: " . substr((string)$body, 0, 500));
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            throw new GatewayException('Tpay: invalid JSON response');
        }
        return $data;
    }
}
