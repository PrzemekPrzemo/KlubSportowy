<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Ksef\XAdESSigner;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Unit-test XAdESSigner — smoke check ze podpisany XML jest poprawny.
 *
 * Strategia: zamiast wstrzykiwac DB + Encryption, korzystamy z prywatnej
 * metody envelopeSign() przez Reflection — daje czysty test bez side-effects.
 * To pokazuje ze logika kryptograficzna dziala, niezaleznie od warstwy
 * persistence.
 */
class XAdESSignerTest extends TestCase
{
    private ?string $certPem = null;
    /** @var \OpenSSLAsymmetricKey|null */
    private $privKey = null;

    protected function setUp(): void
    {
        if (!function_exists('openssl_csr_new')) {
            $this->markTestSkipped('openssl extension not available');
        }
        // Generuj self-signed cert w pamieci
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($privateKey === false) {
            $this->markTestSkipped('cannot generate test RSA key (openssl config?)');
        }
        $dn  = ['commonName' => 'ClubDesk Test', 'countryName' => 'PL'];
        $csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);
        if ($csr === false) {
            $this->markTestSkipped('cannot generate CSR');
        }
        $cert = openssl_csr_sign($csr, null, $privateKey, 1, ['digest_alg' => 'sha256']);
        if ($cert === false) {
            $this->markTestSkipped('cannot sign certificate');
        }
        $certPem = '';
        openssl_x509_export($cert, $certPem);
        $this->certPem = $certPem;
        $this->privKey = $privateKey;
    }

    public function testEnvelopeSignProducesSignatureValue(): void
    {
        if ($this->certPem === null || $this->privKey === null) {
            $this->markTestSkipped('setUp failed to provision test cert');
        }
        $signer = new XAdESSigner();
        $refl   = new ReflectionClass($signer);

        $buildMethod = $refl->getMethod('buildUnsignedSessionTokenXml');
        $buildMethod->setAccessible(true);
        $signMethod = $refl->getMethod('envelopeSign');
        $signMethod->setAccessible(true);

        $challenge = base64_encode('test-challenge-' . random_bytes(8));
        $nip       = '5252289373';

        $unsigned = $buildMethod->invoke($signer, $challenge, $nip);
        $signed   = $signMethod->invoke($signer, $unsigned, $this->certPem, $this->privKey);

        $this->assertIsString($signed);
        $this->assertNotEmpty($signed);
        $this->assertStringContainsString('<ds:Signature', $signed);
        $this->assertStringContainsString('<ds:SignatureValue>', $signed);
        $this->assertStringContainsString('<ds:X509Certificate>', $signed);
        // Reference URI="" pokrywajaca caly dokument
        $this->assertMatchesRegularExpression('/<ds:Reference URI=""/', $signed);
        // XAdES SignedProperties
        $this->assertStringContainsString('SignedProperties', $signed);
        $this->assertStringContainsString('SigningTime', $signed);
        $this->assertStringContainsString('SigningCertificate', $signed);
    }

    public function testSignedDocumentIsParsableXml(): void
    {
        if ($this->certPem === null || $this->privKey === null) {
            $this->markTestSkipped('setUp failed to provision test cert');
        }
        $signer = new XAdESSigner();
        $refl   = new ReflectionClass($signer);
        $buildMethod = $refl->getMethod('buildUnsignedSessionTokenXml');
        $buildMethod->setAccessible(true);
        $signMethod = $refl->getMethod('envelopeSign');
        $signMethod->setAccessible(true);

        $unsigned = $buildMethod->invoke($signer, base64_encode('chal'), '5252289373');
        $signed   = $signMethod->invoke($signer, $unsigned, $this->certPem, $this->privKey);

        $prev = libxml_use_internal_errors(true);
        $doc  = new \DOMDocument();
        $ok   = $doc->loadXML($signed);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $this->assertTrue($ok, 'Signed XML must be parsable');
    }

    public function testSignChallengeRejectsBadNip(): void
    {
        $signer = new XAdESSigner();
        $this->expectException(RuntimeException::class);
        // 9 cyfr → invalid
        $signer->signChallenge('challenge', '123456789', 1);
    }

    public function testSignChallengeRejectsEmptyChallenge(): void
    {
        $signer = new XAdESSigner();
        $this->expectException(RuntimeException::class);
        $signer->signChallenge('', '5252289373', 1);
    }
}
