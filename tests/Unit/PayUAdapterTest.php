<?php

namespace Tests\Unit;

use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\GatewayFactory;
use App\Helpers\Gateway\PayUAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @group unit
 *
 * Faza T.2 — testy adaptera PayU.
 */
class PayUAdapterTest extends TestCase
{
    private array $config = [
        'merchant_id'    => '300746',  // PayU sandbox merchant id (przykład z dokumentacji)
        'api_key'        => '300746',
        'api_secret'     => 'test-secret',
        'webhook_secret' => 'second-key-md5',
        'is_sandbox'     => 1,
    ];

    public function testFactoryReturnsPayUAdapter(): void
    {
        $adapter = GatewayFactory::forProvider('payu', $this->config);
        $this->assertInstanceOf(PayUAdapter::class, $adapter);
        $this->assertSame('payu', $adapter->providerKey());
    }

    public function testWebhookMissingSignatureHeaderThrows(): void
    {
        $adapter = new PayUAdapter($this->config);
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('missing OpenPayu-Signature');
        $adapter->verifyWebhook('{"order":{}}', []);
    }

    public function testWebhookSignatureMismatchThrows(): void
    {
        $adapter = new PayUAdapter($this->config);
        $payload = json_encode(['order' => ['orderId' => 'X', 'status' => 'COMPLETED']]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('signature mismatch');
        $adapter->verifyWebhook($payload, [
            'OpenPayu-Signature' => 'sender=checkout;algorithm=MD5;signature=wrong-sig',
        ]);
    }

    public function testWebhookCorrectMd5SignaturePasses(): void
    {
        $adapter = new PayUAdapter($this->config);
        $payload = json_encode([
            'order' => [
                'orderId'      => 'PAYU_ABC123',
                'extOrderId'   => 'due#42',
                'status'       => 'COMPLETED',
                'totalAmount'  => '5000',
                'currencyCode' => 'PLN',
            ],
        ]);
        // Generujemy poprawną sygnaturę = MD5(payload + secret)
        $sig = md5($payload . $this->config['webhook_secret']);

        $event = $adapter->verifyWebhook($payload, [
            'OpenPayu-Signature' => "sender=checkout;algorithm=MD5;signature={$sig}",
        ]);

        $this->assertSame('paid', $event->status);
        $this->assertSame('PAYU_ABC123', $event->externalId);
        $this->assertSame('due#42', $event->internalReference);
        $this->assertSame(50.0, $event->amount);
        $this->assertSame('PLN', $event->currency);
    }

    public function testWebhookCancelledStatus(): void
    {
        $adapter = new PayUAdapter($this->config);
        $payload = json_encode([
            'order' => ['orderId' => 'PAYU_X', 'status' => 'CANCELED', 'totalAmount' => '100', 'currencyCode' => 'PLN'],
        ]);
        $sig = md5($payload . $this->config['webhook_secret']);
        $event = $adapter->verifyWebhook($payload, [
            'OpenPayu-Signature' => "algorithm=MD5;signature={$sig}",
        ]);
        $this->assertSame('cancelled', $event->status);
    }

    public function testWebhookSecretMissingThrows(): void
    {
        $adapter = new PayUAdapter(array_merge($this->config, ['webhook_secret' => '']));
        $payload = '{"order":{}}';
        $sig = md5($payload . 'whatever');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('webhook_secret not configured');
        $adapter->verifyWebhook($payload, [
            'OpenPayu-Signature' => "algorithm=MD5;signature={$sig}",
        ]);
    }

    public function testHostSandboxVsProduction(): void
    {
        $sandbox = new PayUAdapter(array_merge($this->config, ['is_sandbox' => 1]));
        $prod    = new PayUAdapter(array_merge($this->config, ['is_sandbox' => 0]));

        $rc = new ReflectionClass($sandbox);
        $method = $rc->getMethod('host');
        $method->setAccessible(true);

        $this->assertStringContainsString('snd', $method->invoke($sandbox)); // secure.snd.payu.com
        $this->assertStringNotContainsString('snd', $method->invoke($prod));
    }
}
