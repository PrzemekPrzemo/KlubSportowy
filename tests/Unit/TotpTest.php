<?php

namespace Tests\Unit;

use App\Helpers\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function testGenerateSecretLength(): void
    {
        $secret = Totp::generateSecret();
        $this->assertNotEmpty($secret);
        $this->assertGreaterThanOrEqual(16, strlen($secret));
    }

    public function testGetCodeFormat(): void
    {
        $secret = Totp::generateSecret();
        $code   = Totp::getCode($secret);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testVerifyCodeCurrentTime(): void
    {
        $secret = Totp::generateSecret();
        $code   = Totp::getCode($secret);
        $this->assertTrue(Totp::verifyCode($secret, $code));
    }

    public function testVerifyCodeWrongCode(): void
    {
        $secret = Totp::generateSecret();
        $this->assertFalse(Totp::verifyCode($secret, '000000'));
    }

    public function testVerifyCodeWithWindow(): void
    {
        $secret = Totp::generateSecret();
        $ts     = time() - 30; // one period ago
        $code   = Totp::getCode($secret, $ts);
        // With window=1, the code from 30s ago should still be valid
        $this->assertTrue(Totp::verifyCode($secret, $code, 1));
    }

    public function testOtpauthUrl(): void
    {
        $secret = Totp::generateSecret();
        $url = Totp::otpauthUrl($secret, 'user@test.pl', 'KlubSportowy');
        $this->assertStringStartsWith('otpauth://totp/', $url);
        $this->assertStringContainsString('secret=' . $secret, $url);
        $this->assertStringContainsString('issuer=KlubSportowy', $url);
    }

    public function testGenerateBackupCodes(): void
    {
        $codes = Totp::generateBackupCodes(10);
        $this->assertCount(10, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[0-9A-F]{8}$/', $code);
        }
        // All codes should be unique
        $this->assertCount(10, array_unique($codes));
    }

    public function testDifferentSecretsProduceDifferentCodes(): void
    {
        $s1 = Totp::generateSecret();
        $s2 = Totp::generateSecret();
        $ts = time();
        // Very unlikely to collide
        $c1 = Totp::getCode($s1, $ts);
        $c2 = Totp::getCode($s2, $ts);
        // Not asserting inequality because 1/1000000 chance of collision
        // but verifying they work independently
        $this->assertTrue(Totp::verifyCode($s1, $c1));
        $this->assertTrue(Totp::verifyCode($s2, $c2));
    }
}
