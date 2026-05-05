<?php

namespace Tests\Unit;

use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\GatewayFactory;
use App\Helpers\Gateway\TpayAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @group unit
 *
 * Faza T.4 — testy adaptera Tpay.
 */
class TpayAdapterTest extends TestCase
{
    private array $config = [
        'merchant_id'    => '12345',
        'api_key'        => 'tpay-client-id',
        'api_secret'     => 'tpay-client-secret',
        'webhook_secret' => 'tpay-security-code',
        'is_sandbox'     => 1,
    ];

    public function testFactoryReturnsTpayAdapter(): void
    {
        $adapter = GatewayFactory::forProvider('tpay', $this->config);
        $this->assertInstanceOf(TpayAdapter::class, $adapter);
        $this->assertSame('tpay', $adapter->providerKey());
    }

    public function testWebhookCorrectMd5SignaturePasses(): void
    {
        $adapter = new TpayAdapter($this->config);

        // Tpay legacy notification — form-encoded
        $id       = '12345';
        $trId     = 'TPY12345ABC';
        $trAmount = '50.00';
        $trCrc    = 'due#42';
        $secret   = $this->config['webhook_secret'];

        // md5sum = md5(id + tr_id + tr_amount + tr_crc + security_code)
        $md5sum = md5($id . $trId . $trAmount . $trCrc . $secret);

        $payload = http_build_query([
            'id'        => $id,
            'tr_id'     => $trId,
            'tr_amount' => $trAmount,
            'tr_crc'    => $trCrc,
            'tr_status' => 'TRUE',
            'md5sum'    => $md5sum,
        ]);

        $event = $adapter->verifyWebhook($payload, []);
        $this->assertSame('paid',   $event->status);
        $this->assertSame($trId,    $event->externalId);
        $this->assertSame(50.0,     $event->amount);
        $this->assertSame('due#42', $event->internalReference);
    }

    public function testWebhookSignatureMismatchThrows(): void
    {
        $adapter = new TpayAdapter($this->config);

        $payload = http_build_query([
            'id'        => '12345',
            'tr_id'     => 'TPY1',
            'tr_amount' => '10.00',
            'tr_crc'    => 'due#1',
            'tr_status' => 'TRUE',
            'md5sum'    => 'wrong_md5_here',
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('signature mismatch');
        $adapter->verifyWebhook($payload, []);
    }

    public function testWebhookMissingFieldThrows(): void
    {
        $adapter = new TpayAdapter($this->config);
        $payload = http_build_query(['id' => 'X']); // brak tr_id, tr_amount itd.

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('missing field');
        $adapter->verifyWebhook($payload, []);
    }

    public function testWebhookFalseStatusMapping(): void
    {
        $adapter = new TpayAdapter($this->config);
        $secret = $this->config['webhook_secret'];

        $md5sum = md5('1' . 'X' . '10.00' . 'cr' . $secret);
        $payload = http_build_query([
            'id' => '1', 'tr_id' => 'X', 'tr_amount' => '10.00', 'tr_crc' => 'cr',
            'tr_status' => 'FALSE', 'md5sum' => $md5sum,
        ]);
        $event = $adapter->verifyWebhook($payload, []);
        $this->assertSame('failed', $event->status);
    }

    public function testWebhookSecretMissingThrows(): void
    {
        $adapter = new TpayAdapter(array_merge($this->config, ['webhook_secret' => '']));
        $payload = http_build_query([
            'id' => '1', 'tr_id' => 'X', 'tr_amount' => '10', 'tr_crc' => 'cr',
            'tr_status' => 'TRUE', 'md5sum' => md5('whatever'),
        ]);
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('webhook_secret not configured');
        $adapter->verifyWebhook($payload, []);
    }

    public function testHostSandboxVsProduction(): void
    {
        $sandbox = new TpayAdapter(array_merge($this->config, ['is_sandbox' => 1]));
        $prod    = new TpayAdapter(array_merge($this->config, ['is_sandbox' => 0]));

        $rc = new ReflectionClass($sandbox);
        $method = $rc->getMethod('host');
        $method->setAccessible(true);

        $this->assertStringContainsString('sandbox', $method->invoke($sandbox));
        $this->assertStringNotContainsString('sandbox', $method->invoke($prod));
    }
}
