<?php

namespace Tests\Unit;

use App\Helpers\Webhooks\WebhookDispatcher;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Pure-function tests dla HMAC-SHA256 signing webhook payloadow.
 * Klienci weryfikuja: hash_equals("sha256=" . hash_hmac('sha256', $body, $secret), $headerVal).
 */
class WebhookSignatureTest extends TestCase
{
    public function testSignProducesHexHmacSha256(): void
    {
        $sig = WebhookDispatcher::sign('{"foo":"bar"}', 'topsecret');
        // SHA-256 hex = 64 chars
        $this->assertSame(64, strlen($sig));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $sig);
    }

    public function testKnownVector(): void
    {
        // Reference: hash_hmac('sha256', 'hello', 'key') = same wartosc na kazdej platformie.
        $expected = hash_hmac('sha256', 'hello', 'key');
        $this->assertSame($expected, WebhookDispatcher::sign('hello', 'key'));
    }

    public function testDifferentSecretsProduceDifferentSignatures(): void
    {
        $body = '{"event":"member.created","data":{"id":1}}';
        $a = WebhookDispatcher::sign($body, 'secret-a');
        $b = WebhookDispatcher::sign($body, 'secret-b');
        $this->assertNotSame($a, $b);
    }

    public function testDifferentBodiesProduceDifferentSignatures(): void
    {
        $secret = bin2hex(random_bytes(16));
        $a = WebhookDispatcher::sign('{"a":1}', $secret);
        $b = WebhookDispatcher::sign('{"a":2}', $secret);
        $this->assertNotSame($a, $b);
    }

    public function testSigningIsDeterministic(): void
    {
        $body   = json_encode(['event' => 'test', 'data' => ['n' => 42]]);
        $secret = 'shared-secret-32-byte-random-hex-string';
        $s1 = WebhookDispatcher::sign($body, $secret);
        $s2 = WebhookDispatcher::sign($body, $secret);
        $this->assertSame($s1, $s2);
    }

    public function testClientSideVerificationFlow(): void
    {
        // Symuluje co bedzie robil klient odbierajacy webhook.
        $rawBody = '{"event":"payment.received","club_id":42,"data":{"amount":100.0}}';
        $secret  = 'cdk-webhook-secret-example';

        // Serwer (ClubDesk) podpisuje:
        $headerValue = 'sha256=' . WebhookDispatcher::sign($rawBody, $secret);

        // Klient weryfikuje:
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        $this->assertTrue(hash_equals($expected, $headerValue));

        // Negatywny — modyfikacja payloadu wykryta:
        $tamperedBody = $rawBody . ' ';
        $expectedTampered = 'sha256=' . hash_hmac('sha256', $tamperedBody, $secret);
        $this->assertFalse(hash_equals($expectedTampered, $headerValue));
    }
}
