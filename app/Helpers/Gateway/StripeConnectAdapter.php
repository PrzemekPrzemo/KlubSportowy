<?php

namespace App\Helpers\Gateway;

/**
 * Stripe Connect (Express) adapter — split payments.
 *
 * Klub jest connected account (typ Express), ClubDesk (Sendormeco) jest
 * platformą. Przy każdej transakcji potrącana jest `application_fee_amount`,
 * reszta jest transferowana do konta klubu.
 *
 * Wymaga STRIPE_SECRET_KEY platformy (sk_live/sk_test, klucz ClubDesk,
 * NIE klubu) — przekazywany przez config['platform_api_key'] lub fallback na
 * config['api_key'].
 *
 * API endpoints:
 *   POST /v1/accounts                    — create Express account
 *   POST /v1/account_links               — onboarding link
 *   GET  /v1/accounts/{id}               — status (kyc/charges/payouts)
 *   POST /v1/checkout/sessions           — checkout with destination + fee
 *
 * Implementacja: natywny cURL (jak Przelewy24Adapter) — bez SDK, żeby
 * uniknąć kolizji setApiKey z istniejącym StripeAdapter (klucze klubu).
 *
 * @link https://stripe.com/docs/connect/express-accounts
 * @link https://stripe.com/docs/api/accounts
 */
class StripeConnectAdapter
{
    private const API_BASE = 'https://api.stripe.com';

    public function __construct(
        private readonly array $config,
    ) {
    }

    private function platformKey(): string
    {
        $k = (string)($this->config['platform_api_key'] ?? $this->config['api_key'] ?? '');
        if ($k === '') {
            throw new GatewayException('Stripe Connect: platform_api_key not configured');
        }
        return $k;
    }

    /**
     * Utwórz Express connected account dla klubu.
     *
     * @return array {id: string, type: string, country: string, ...raw}
     */
    public function createConnectAccount(int $clubId, string $email = '', string $country = 'PL'): array
    {
        $payload = [
            'type'    => 'express',
            'country' => $country,
            'capabilities[card_payments][requested]' => 'true',
            'capabilities[transfers][requested]'     => 'true',
            'metadata[club_id]' => (string)$clubId,
        ];
        if ($email !== '') {
            $payload['email'] = $email;
        }
        return $this->httpPost('/v1/accounts', $payload);
    }

    /**
     * Wygeneruj jednorazowy link onboardingu (Stripe-hosted UI).
     * Po ukończeniu / abandon Stripe redirect do return_url / refresh_url.
     *
     * @return string URL do redirectu
     */
    public function getOnboardingLink(string $accountId, string $returnUrl, ?string $refreshUrl = null): string
    {
        $payload = [
            'account'     => $accountId,
            'refresh_url' => $refreshUrl ?? $returnUrl,
            'return_url'  => $returnUrl,
            'type'        => 'account_onboarding',
        ];
        $resp = $this->httpPost('/v1/account_links', $payload);
        $url = $resp['url'] ?? null;
        if (!$url) {
            throw new GatewayException('Stripe account_links: no url returned: ' . json_encode($resp));
        }
        return (string)$url;
    }

    /**
     * Pobierz status connected account.
     *
     * @return array{
     *   id:string,
     *   charges_enabled:bool,
     *   payouts_enabled:bool,
     *   details_submitted:bool,
     *   kyc_status:string,   // pending|verified|restricted|rejected
     *   capabilities:array,
     *   raw:array
     * }
     */
    public function getAccountStatus(string $accountId): array
    {
        $resp = $this->httpGet('/v1/accounts/' . rawurlencode($accountId));

        $chargesEnabled  = (bool)($resp['charges_enabled'] ?? false);
        $payoutsEnabled  = (bool)($resp['payouts_enabled'] ?? false);
        $detailsSubmitted= (bool)($resp['details_submitted'] ?? false);

        $kyc = 'pending';
        $requirements = $resp['requirements'] ?? [];
        $disabledReason = $requirements['disabled_reason'] ?? null;
        if ($detailsSubmitted && $chargesEnabled && $payoutsEnabled) {
            $kyc = 'verified';
        } elseif ($disabledReason && str_contains((string)$disabledReason, 'rejected')) {
            $kyc = 'rejected';
        } elseif ($disabledReason !== null && $disabledReason !== '') {
            $kyc = 'restricted';
        }

        return [
            'id'                => (string)($resp['id'] ?? $accountId),
            'charges_enabled'   => $chargesEnabled,
            'payouts_enabled'   => $payoutsEnabled,
            'details_submitted' => $detailsSubmitted,
            'kyc_status'        => $kyc,
            'capabilities'      => $resp['capabilities'] ?? [],
            'raw'               => $resp,
        ];
    }

    /**
     * Utwórz Checkout Session z split: application_fee + transfer do klubu.
     *
     * @param string $clubAccountId    acct_xxx connected account klubu
     * @param int    $amountCents      kwota brutto (grosze)
     * @param int    $applicationFeeCents prowizja platformy (grosze)
     * @param string $currency         ISO 4217 (np. PLN)
     * @param string $description      nazwa produktu
     * @param string $successUrl
     * @param string $cancelUrl
     * @param array  $metadata         arbitralne metadane (club_id, member_id, reference)
     * @return array{redirect_url:string, session_id:string, payment_intent:?string}
     */
    public function createCheckoutWithFee(
        string $clubAccountId,
        int $amountCents,
        int $applicationFeeCents,
        string $currency,
        string $description,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
        ?string $customerEmail = null
    ): array {
        if ($applicationFeeCents < 0 || $applicationFeeCents > $amountCents) {
            throw new GatewayException(sprintf(
                'Invalid application_fee_amount: %d (gross=%d)',
                $applicationFeeCents,
                $amountCents
            ));
        }

        $payload = [
            'mode'                          => 'payment',
            'payment_method_types[]'        => 'card',
            'success_url'                   => $successUrl,
            'cancel_url'                    => $cancelUrl,
            'line_items[0][price_data][currency]'       => strtolower($currency ?: 'pln'),
            'line_items[0][price_data][product_data][name]' => mb_substr($description, 0, 250),
            'line_items[0][price_data][unit_amount]'    => (string)$amountCents,
            'line_items[0][quantity]'                   => '1',
            'payment_intent_data[application_fee_amount]' => (string)$applicationFeeCents,
            'payment_intent_data[transfer_data][destination]' => $clubAccountId,
        ];
        if ($customerEmail) {
            $payload['customer_email'] = $customerEmail;
        }
        foreach ($metadata as $k => $v) {
            $payload['metadata[' . $k . ']'] = (string)$v;
            $payload['payment_intent_data[metadata][' . $k . ']'] = (string)$v;
        }

        $resp = $this->httpPost('/v1/checkout/sessions', $payload);
        return [
            'redirect_url'   => (string)($resp['url'] ?? ''),
            'session_id'     => (string)($resp['id'] ?? ''),
            'payment_intent' => isset($resp['payment_intent']) ? (string)$resp['payment_intent'] : null,
        ];
    }

    // ── HTTP helpers ────────────────────────────────────────────────────

    private function httpPost(string $path, array $form): array
    {
        return $this->request('POST', $path, $form);
    }

    private function httpGet(string $path): array
    {
        return $this->request('GET', $path, null);
    }

    private function request(string $method, string $path, ?array $form): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_BASE . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->platformKey(),
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form ?? []));
        }
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new GatewayException('Stripe Connect cURL error: ' . $err);
        }
        $json = json_decode((string)$body, true);
        if (!is_array($json)) {
            throw new GatewayException('Stripe Connect: non-JSON response (HTTP ' . $code . ')');
        }
        if ($code >= 400) {
            $msg = $json['error']['message'] ?? $json['error']['type'] ?? 'HTTP ' . $code;
            throw new GatewayException('Stripe Connect API error: ' . $msg);
        }
        return $json;
    }
}
