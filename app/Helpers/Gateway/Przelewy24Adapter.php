<?php

namespace App\Helpers\Gateway;

/**
 * Przelewy24 (P24) adapter — implementuje GatewayAdapterInterface.
 *
 * Protocol (P24 REST API v1):
 *   1. POST /api/v1/transaction/register   → otrzymujemy `token`
 *   2. Redirect user do https://secure.przelewy24.pl/trnRequest/{token}
 *   3. P24 wywołuje webhook (urlStatus) z payload + signature
 *   4. POST /api/v1/transaction/verify     → potwierdzenie po stronie sklepu
 *
 * Konfiguracja (z club_payment_gateways z P.5):
 *   - merchant_id     — Merchant ID (numer 6-cyfrowy)
 *   - api_key         — POS verification API key (do HTTP Basic auth)
 *   - crc_key         — CRC key z panelu P24 (używany do sygnatur SHA384)
 *   - is_sandbox      — true/false (sandbox.przelewy24.pl vs secure.przelewy24.pl)
 *
 * Bez external SDK — używamy raw cURL bo P24 SDK na composer jest słabo
 * utrzymywany. Sygnatury SHA384 nad JSON-em — w PHP natywnie.
 *
 * @link https://developers.przelewy24.pl/index.php?en
 */
class Przelewy24Adapter implements GatewayAdapterInterface
{
    private const SANDBOX_HOST = 'https://sandbox.przelewy24.pl';
    private const PROD_HOST    = 'https://secure.przelewy24.pl';

    public function __construct(
        private readonly array $config,
    ) {
    }

    public function providerKey(): string
    {
        return 'przelewy24';
    }

    public function createCheckout(CheckoutRequest $req): CheckoutResult
    {
        $this->assertConfigured();

        // Session ID musi być unikalne per transakcja, max 100 znaków.
        // Używamy internalReference + nanosecond timestamp dla idempotency.
        $sessionId = substr($req->internalReference . '_' . hrtime(true), 0, 100);
        $amountCents = (int)round($req->amount * 100);
        $currency    = strtoupper($req->currency ?: 'PLN');

        $payload = [
            'merchantId'  => (int)$this->config['merchant_id'],
            'posId'       => (int)$this->config['merchant_id'],
            'sessionId'   => $sessionId,
            'amount'      => $amountCents,
            'currency'    => $currency,
            'description' => mb_substr($req->description, 0, 1024),
            'email'       => $req->customerEmail ?? '',
            'country'     => 'PL',
            'language'    => 'pl',
            'urlReturn'   => $req->successUrl,
            'urlStatus'   => $req->notifyUrl,
            'sign'        => $this->signRegister($sessionId, $amountCents, $currency),
        ];

        $resp = $this->httpPost('/api/v1/transaction/register', $payload);
        $token = $resp['data']['token'] ?? null;
        if (!$token) {
            throw new GatewayException(
                'Przelewy24 registration failed: ' . json_encode($resp, JSON_UNESCAPED_UNICODE)
            );
        }

        return new CheckoutResult(
            redirectUrl: $this->host() . '/trnRequest/' . $token,
            externalId:  $sessionId,
            rawResponse: ['token' => $token, 'session_id' => $sessionId],
        );
    }

    public function verifyWebhook(string $rawPayload, array $headers): WebhookEvent
    {
        $data = json_decode($rawPayload, true);
        if (!is_array($data)) {
            throw new GatewayException('Przelewy24 webhook: invalid JSON payload');
        }

        $required = ['merchantId', 'posId', 'sessionId', 'amount', 'currency', 'orderId', 'sign'];
        foreach ($required as $f) {
            if (!isset($data[$f])) {
                throw new GatewayException("Przelewy24 webhook: missing field '{$f}'");
            }
        }

        // Weryfikacja sygnatury — SHA-384 nad JSON-em z stałą kolejnością pól + crc_key
        $expectedSign = $this->signWebhook(
            (string)$data['sessionId'],
            (int)$data['orderId'],
            (int)$data['amount'],
            (string)$data['currency']
        );
        if (!hash_equals($expectedSign, (string)$data['sign'])) {
            throw new GatewayException('Przelewy24 webhook: signature mismatch');
        }

        // Webhook P24 oznacza że transakcja jest "do potwierdzenia". Według
        // protokołu po webhook'u musimy zawołać /transaction/verify żeby
        // ostatecznie potwierdzić — robimy to inline.
        $verified = $this->verifyTransaction(
            (string)$data['sessionId'],
            (int)$data['orderId'],
            (int)$data['amount'],
            (string)$data['currency']
        );

        $status = $verified ? WebhookEvent::STATUS_PAID : WebhookEvent::STATUS_FAILED;

        return new WebhookEvent(
            externalId:        (string)$data['sessionId'],
            status:            $status,
            amount:            ((int)$data['amount']) / 100,
            currency:          strtoupper((string)$data['currency']),
            internalReference: (string)$data['sessionId'], // P24 nie ma osobnego ref
            rawPayload:        $data,
        );
    }

    public function fetchStatus(string $externalId): TransactionStatus
    {
        $this->assertConfigured();

        // GET /api/v1/transaction/by/sessionId/{sessionId}
        $resp = $this->httpGet('/api/v1/transaction/by/sessionId/' . urlencode($externalId));
        $tr = $resp['data'] ?? [];
        if (!$tr) {
            throw new GatewayException('Przelewy24 fetchStatus: transaction not found');
        }

        // P24 status: 1=new, 2=pending, 3=successful, 4=failed (uproszczone)
        $statusCode = (int)($tr['status'] ?? 0);
        $status = match ($statusCode) {
            3       => WebhookEvent::STATUS_PAID,
            4       => WebhookEvent::STATUS_FAILED,
            default => WebhookEvent::STATUS_PENDING,
        };

        return new TransactionStatus(
            externalId:  $externalId,
            status:      $status,
            amount:      isset($tr['amount']) ? ((int)$tr['amount']) / 100 : null,
            currency:    strtoupper($tr['currency'] ?? 'PLN'),
            rawResponse: $tr,
        );
    }

    /**
     * Wywołanie /api/v1/transaction/verify — ostateczne potwierdzenie
     * transakcji po webhook'u. Zwraca true gdy P24 odpowiedziało success.
     */
    private function verifyTransaction(string $sessionId, int $orderId, int $amountCents, string $currency): bool
    {
        $payload = [
            'merchantId' => (int)$this->config['merchant_id'],
            'posId'      => (int)$this->config['merchant_id'],
            'sessionId'  => $sessionId,
            'amount'     => $amountCents,
            'currency'   => strtoupper($currency),
            'orderId'    => $orderId,
            'sign'       => $this->signVerify($sessionId, $orderId, $amountCents, $currency),
        ];
        try {
            $resp = $this->httpPut('/api/v1/transaction/verify', $payload);
            return ($resp['data']['status'] ?? null) === 'success';
        } catch (\Throwable $e) {
            // Verify error — log + return false (transakcja nie zostanie booked)
            error_log('Przelewy24 verify failed: ' . $e->getMessage());
            return false;
        }
    }

    private function signRegister(string $sessionId, int $amountCents, string $currency): string
    {
        $crc = (string)$this->config['crc_key'];
        $body = json_encode([
            'sessionId'  => $sessionId,
            'merchantId' => (int)$this->config['merchant_id'],
            'amount'     => $amountCents,
            'currency'   => strtoupper($currency),
            'crc'        => $crc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha384', $body);
    }

    private function signWebhook(string $sessionId, int $orderId, int $amountCents, string $currency): string
    {
        $crc = (string)$this->config['crc_key'];
        $body = json_encode([
            'merchantId' => (int)$this->config['merchant_id'],
            'posId'      => (int)$this->config['merchant_id'],
            'sessionId'  => $sessionId,
            'amount'     => $amountCents,
            'currency'   => strtoupper($currency),
            'orderId'    => $orderId,
            'crc'        => $crc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha384', $body);
    }

    private function signVerify(string $sessionId, int $orderId, int $amountCents, string $currency): string
    {
        $crc = (string)$this->config['crc_key'];
        $body = json_encode([
            'sessionId' => $sessionId,
            'orderId'   => $orderId,
            'amount'    => $amountCents,
            'currency'  => strtoupper($currency),
            'crc'       => $crc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha384', $body);
    }

    private function host(): string
    {
        return !empty($this->config['is_sandbox']) ? self::SANDBOX_HOST : self::PROD_HOST;
    }

    private function assertConfigured(): void
    {
        foreach (['merchant_id', 'api_key', 'crc_key'] as $f) {
            if (empty($this->config[$f])) {
                throw new GatewayException("Przelewy24 missing config: {$f}");
            }
        }
    }

    private function httpPost(string $path, array $payload): array
    {
        return $this->http('POST', $path, $payload);
    }

    private function httpPut(string $path, array $payload): array
    {
        return $this->http('PUT', $path, $payload);
    }

    private function httpGet(string $path): array
    {
        return $this->http('GET', $path, null);
    }

    private function http(string $method, string $path, ?array $payload): array
    {
        $url = $this->host() . $path;
        $ch = curl_init($url);
        $auth = $this->config['merchant_id'] . ':' . $this->config['api_key'];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $auth,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
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
            throw new GatewayException("Przelewy24 cURL error: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new GatewayException("Przelewy24 HTTP {$code}: " . substr($body, 0, 500));
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new GatewayException('Przelewy24: invalid JSON response');
        }
        return $data;
    }
}
