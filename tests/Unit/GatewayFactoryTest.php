<?php

namespace Tests\Unit;

use App\Helpers\Gateway\CheckoutRequest;
use App\Helpers\Gateway\GatewayFactory;
use App\Helpers\Gateway\StripeAdapter;
use App\Helpers\Gateway\WebhookEvent;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Faza T.0 — testy fundamentu bramek płatności.
 * Bez DB ani prawdziwych API call'i — testujemy:
 *   - factory routing per provider
 *   - DTO immutability
 *   - WebhookEvent constants
 */
class GatewayFactoryTest extends TestCase
{
    public function testForProviderReturnsStripeAdapter(): void
    {
        $config = ['api_key' => 'sk_test_xxx', 'webhook_secret' => 'whsec_xxx'];
        $adapter = GatewayFactory::forProvider('stripe', $config);

        $this->assertInstanceOf(StripeAdapter::class, $adapter);
        $this->assertSame('stripe', $adapter->providerKey());
    }

    public function testForProviderReturnsNullForManual(): void
    {
        $this->assertNull(GatewayFactory::forProvider('manual', []));
    }

    public function testForProviderReturnsNullForUnknown(): void
    {
        $this->assertNull(GatewayFactory::forProvider('foobar', []));
    }

    public function testForProviderReturnsNullForUnimplementedAdapters(): void
    {
        // T.2 (PayU) i T.4 (Tpay) jeszcze nie zaimplementowane —
        // factory zwraca null aż klasy zostaną dodane.
        // T.1 Przelewy24 dostępne (PR #75).
        $config = ['api_key' => 'test'];
        $this->assertNull(GatewayFactory::forProvider('payu', $config));
        $this->assertNull(GatewayFactory::forProvider('tpay', $config));
    }

    public function testCheckoutRequestIsImmutable(): void
    {
        $req = new CheckoutRequest(
            clubId:            1,
            memberId:          42,
            amount:            100.50,
            currency:          'PLN',
            description:       'Test',
            successUrl:        'http://localhost/success',
            cancelUrl:         'http://localhost/cancel',
            notifyUrl:         'http://localhost/notify',
            internalReference: 'due#42',
        );

        $this->assertSame(1,   $req->clubId);
        $this->assertSame(42,  $req->memberId);
        $this->assertSame(100.50, $req->amount);
        $this->assertSame('PLN', $req->currency);
        $this->assertSame('due#42', $req->internalReference);
    }

    public function testWebhookEventStatusConstants(): void
    {
        $this->assertSame('paid',      WebhookEvent::STATUS_PAID);
        $this->assertSame('pending',   WebhookEvent::STATUS_PENDING);
        $this->assertSame('failed',    WebhookEvent::STATUS_FAILED);
        $this->assertSame('refunded',  WebhookEvent::STATUS_REFUNDED);
        $this->assertSame('cancelled', WebhookEvent::STATUS_CANCELLED);
    }
}
