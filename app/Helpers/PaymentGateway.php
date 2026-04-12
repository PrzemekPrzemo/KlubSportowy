<?php

namespace App\Helpers;

use App\Models\ClubSettingsModel;

/**
 * Abstrakcja bramki płatności — Stripe / Przelewy24 / manual.
 *
 * Konfiguracja per-klub: club_settings.stripe_secret_key, stripe_webhook_secret.
 * Fallback: manual (admin ręcznie oznacza fakturę jako opłaconą).
 *
 * Stripe wymaga composer require stripe/stripe-php.
 */
class PaymentGateway
{
    /**
     * Tworzy sesję checkout (Stripe) lub zwraca null (manual mode).
     * @return string|null URL do przekierowania (Stripe Checkout) lub null
     */
    public static function createCheckoutSession(int $clubId, float $amount, string $description, string $successUrl, string $cancelUrl): ?string
    {
        $config = self::getConfig($clubId);
        if (!$config['enabled'] || !class_exists('\Stripe\Stripe')) {
            return null; // manual mode
        }

        try {
            \Stripe\Stripe::setApiKey($config['secret_key']);
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'pln',
                        'product_data' => ['name' => $description],
                        'unit_amount'  => (int)round($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
                'metadata'    => ['club_id' => $clubId],
            ]);
            return $session->url;
        } catch (\Throwable $e) {
            error_log('Stripe error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Weryfikuje webhook Stripe (podpis HMAC).
     * @return array|null Payload lub null jeśli podpis nieprawidłowy
     */
    public static function verifyWebhook(string $payload, string $sigHeader, int $clubId): ?array
    {
        $config = self::getConfig($clubId);
        if (!$config['webhook_secret'] || !class_exists('\Stripe\Webhook')) {
            return null;
        }
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $config['webhook_secret']);
            return ['type' => $event->type, 'data' => $event->data->object];
        } catch (\Throwable) {
            return null;
        }
    }

    private static function getConfig(int $clubId): array
    {
        $cs = new ClubSettingsModel();
        return [
            'enabled'        => (bool)$cs->get($clubId, 'stripe_enabled', '0'),
            'secret_key'     => $cs->get($clubId, 'stripe_secret_key', ''),
            'webhook_secret' => $cs->get($clubId, 'stripe_webhook_secret', ''),
        ];
    }
}
