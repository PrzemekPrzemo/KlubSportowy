<?php

namespace App\Helpers\Gateway;

use App\Helpers\ClubContext;
use App\Models\ClubPaymentGatewayModel;

/**
 * Factory wybierająca odpowiedni adapter dla aktywnej bramki klubu.
 *
 * Logika:
 *   1. ClubPaymentGatewayModel.activeGateway() — pobiera wpis WHERE is_active=1
 *   2. Routes per provider do konkretnego adaptera:
 *      - przelewy24 → Przelewy24Adapter (T.1)
 *      - payu       → PayUAdapter (T.2)
 *      - stripe     → StripeAdapter (T.0 — ten PR)
 *      - tpay       → TpayAdapter (T.4)
 *
 * Adaptery z serii T.1-T.4 jeszcze nie istnieją — fallback na null
 * (kontroler porozumiewa się info "manual mode").
 */
class GatewayFactory
{
    /**
     * Zwraca adapter dla aktywnej bramki klubu lub null gdy brak aktywnej
     * lub bramka jest 'manual'.
     */
    public static function forActiveClub(?int $clubId = null): ?GatewayAdapterInterface
    {
        $clubId = $clubId ?? ClubContext::current();
        if ($clubId === null) return null;

        $config = (new ClubPaymentGatewayModel())->activeGateway();
        if (!$config) return null;

        return self::buildAdapter($config['provider'], $config);
    }

    /**
     * Eksplicytnie zbuduj adapter dla danego providera (np. dla testów).
     */
    public static function forProvider(string $provider, array $config): ?GatewayAdapterInterface
    {
        return self::buildAdapter($provider, $config);
    }

    private static function buildAdapter(string $provider, array $config): ?GatewayAdapterInterface
    {
        return match ($provider) {
            'stripe'     => new StripeAdapter($config),
            // Następujące adaptery są stub'ami — czekają na T.1-T.4
            'przelewy24' => class_exists(__NAMESPACE__ . '\\Przelewy24Adapter')
                            ? new (__NAMESPACE__ . '\\Przelewy24Adapter')($config) : null,
            'payu'       => class_exists(__NAMESPACE__ . '\\PayUAdapter')
                            ? new (__NAMESPACE__ . '\\PayUAdapter')($config) : null,
            'tpay'       => class_exists(__NAMESPACE__ . '\\TpayAdapter')
                            ? new (__NAMESPACE__ . '\\TpayAdapter')($config) : null,
            default      => null,
        };
    }
}
