<?php

namespace Tests\Unit;

use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\GatewayFactory;
use App\Helpers\Gateway\Przelewy24Adapter;
use App\Helpers\Gateway\WebhookEvent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @group unit
 *
 * Faza T.1 — testy adaptera Przelewy24.
 * Bez DB ani prawdziwych API call'i — testujemy:
 *   - factory zwraca P24 adapter dla provider='przelewy24'
 *   - sygnatury SHA-384 są deterministyczne
 *   - missing config rzuca GatewayException
 *   - webhook z błędną sygnaturą rzuca GatewayException
 */
class Przelewy24AdapterTest extends TestCase
{
    private array $config = [
        'merchant_id' => '123456',
        'api_key'     => 'api-test-key',
        'crc_key'     => 'crc-test-key',
        'is_sandbox'  => 1,
    ];

    public function testFactoryReturnsP24AdapterForProvider(): void
    {
        $adapter = GatewayFactory::forProvider('przelewy24', $this->config);
        $this->assertInstanceOf(Przelewy24Adapter::class, $adapter);
        $this->assertSame('przelewy24', $adapter->providerKey());
    }

    public function testMissingConfigThrows(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('missing config');
        $adapter = new Przelewy24Adapter(['merchant_id' => '123']); // brak api_key + crc_key
        $req = new \App\Helpers\Gateway\CheckoutRequest(
            clubId: 1, memberId: 1, amount: 50.0, currency: 'PLN',
            description: 'Test', successUrl: 'http://localhost/ok',
            cancelUrl: 'http://localhost/cancel', notifyUrl: 'http://localhost/webhook',
            internalReference: 'due#1',
        );
        $adapter->createCheckout($req);
    }

    public function testWebhookSignatureMismatchThrows(): void
    {
        $adapter = new Przelewy24Adapter($this->config);

        $payload = json_encode([
            'merchantId' => 123456,
            'posId'      => 123456,
            'sessionId'  => 'due#1_xxx',
            'amount'     => 5000,
            'currency'   => 'PLN',
            'orderId'    => 99999,
            'sign'       => 'wrong_signature_here',
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('signature mismatch');
        $adapter->verifyWebhook($payload, []);
    }

    public function testWebhookMissingFieldThrows(): void
    {
        $adapter = new Przelewy24Adapter($this->config);
        $payload = json_encode(['merchantId' => 123456]); // brak innych pól

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('missing field');
        $adapter->verifyWebhook($payload, []);
    }

    public function testWebhookMalformedJsonThrows(): void
    {
        $adapter = new Przelewy24Adapter($this->config);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('invalid JSON');
        $adapter->verifyWebhook('this-is-not-json', []);
    }

    /**
     * Test prywatnej metody signWebhook przez Reflection — sprawdzamy
     * że sygnatura jest deterministyczna i SHA-384 (96 hex chars).
     */
    public function testWebhookSignatureIsDeterministic(): void
    {
        $adapter = new Przelewy24Adapter($this->config);
        $rc = new ReflectionClass($adapter);
        $method = $rc->getMethod('signWebhook');
        $method->setAccessible(true);

        $sig1 = $method->invoke($adapter, 'sess_abc', 11111, 5000, 'PLN');
        $sig2 = $method->invoke($adapter, 'sess_abc', 11111, 5000, 'PLN');

        $this->assertSame($sig1, $sig2, 'Sygnatura musi być deterministyczna');
        $this->assertSame(96, strlen($sig1), 'SHA-384 = 384 bity = 96 hex chars');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{96}$/', $sig1);

        // Inny crc_key → inna sygnatura
        $adapter2 = new Przelewy24Adapter(array_merge($this->config, ['crc_key' => 'different-crc']));
        $rc2 = new ReflectionClass($adapter2);
        $method2 = $rc2->getMethod('signWebhook');
        $method2->setAccessible(true);
        $sig3 = $method2->invoke($adapter2, 'sess_abc', 11111, 5000, 'PLN');

        $this->assertNotSame($sig1, $sig3, 'Inna crc_key musi dawać inną sygnaturę');
    }

    public function testHostSandboxVsProduction(): void
    {
        $sandbox = new Przelewy24Adapter(array_merge($this->config, ['is_sandbox' => 1]));
        $prod    = new Przelewy24Adapter(array_merge($this->config, ['is_sandbox' => 0]));

        $rc = new ReflectionClass($sandbox);
        $hostMethod = $rc->getMethod('host');
        $hostMethod->setAccessible(true);

        $this->assertStringContainsString('sandbox', $hostMethod->invoke($sandbox));
        $this->assertStringNotContainsString('sandbox', $hostMethod->invoke($prod));
    }
}
