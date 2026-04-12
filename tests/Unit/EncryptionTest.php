<?php

namespace Tests\Unit;

use App\Helpers\Encryption;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AES-256-GCM encryption helper.
 *
 * Uses a temporary encryption key file created in setUp and removed in tearDown.
 */
class EncryptionTest extends TestCase
{
    private string $keyFile;
    private string $testKey;

    protected function setUp(): void
    {
        parent::setUp();

        $configDir = ROOT_PATH . '/config';
        $this->keyFile = $configDir . '/encryption.local.php';

        // Generate a deterministic test key (32 bytes, base64)
        $this->testKey = base64_encode(str_repeat('K', 32));

        // Only create if not already present (avoid overwriting a real one)
        if (!file_exists($this->keyFile)) {
            file_put_contents($this->keyFile, "<?php\nreturn ['key' => '{$this->testKey}', 'cipher' => 'aes-256-gcm'];\n");
        }

        // Reset static key cache so getKey() re-reads from file
        Encryption::reset();
    }

    protected function tearDown(): void
    {
        // Remove the test key file we created
        if (isset($this->keyFile) && file_exists($this->keyFile)) {
            unlink($this->keyFile);
        }

        // Reset static cache for next test
        Encryption::reset();

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // encrypt / decrypt
    // ------------------------------------------------------------------

    public function testEncryptDecryptRoundTrip(): void
    {
        $plaintext = 'Tajne dane osobowe 12345';
        $encrypted = Encryption::encrypt($plaintext);

        $this->assertNotNull($encrypted);
        $this->assertNotEquals($plaintext, $encrypted);

        $decrypted = Encryption::decrypt($encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptDecryptUtf8(): void
    {
        $plaintext = 'Zażółć gęślą jaźń — polskie znaki';
        $encrypted = Encryption::encrypt($plaintext);
        $decrypted = Encryption::decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptProducesDifferentCiphertext(): void
    {
        $plaintext = 'same input every time';
        $a = Encryption::encrypt($plaintext);
        $b = Encryption::encrypt($plaintext);

        $this->assertNotEquals($a, $b, 'Each encryption must produce unique ciphertext (random nonce)');

        // But both must decrypt to the same value
        $this->assertEquals($plaintext, Encryption::decrypt($a));
        $this->assertEquals($plaintext, Encryption::decrypt($b));
    }

    public function testDecryptTamperedReturnsNull(): void
    {
        $encrypted = Encryption::encrypt('oryginał');
        $this->assertNotNull($encrypted);

        // Tamper with the base64 payload
        $raw = base64_decode($encrypted, true);
        $raw[5] = $raw[5] === "\x00" ? "\x01" : "\x00"; // flip a byte
        $tampered = base64_encode($raw);

        $result = Encryption::decrypt($tampered);
        $this->assertNull($result, 'Tampered ciphertext must return null');
    }

    public function testDecryptNullReturnsNull(): void
    {
        $this->assertNull(Encryption::decrypt(null));
    }

    public function testDecryptEmptyStringReturnsNull(): void
    {
        $this->assertNull(Encryption::decrypt(''));
    }

    public function testEncryptNullReturnsNull(): void
    {
        $this->assertNull(Encryption::encrypt(null));
    }

    public function testEncryptEmptyStringReturnsNull(): void
    {
        $this->assertNull(Encryption::encrypt(''));
    }

    public function testDecryptGarbageReturnsNull(): void
    {
        $this->assertNull(Encryption::decrypt('not-valid-base64!!!'));
    }

    public function testDecryptTooShortReturnsNull(): void
    {
        // Less than nonce(12) + tag(16) + 1 byte ciphertext
        $this->assertNull(Encryption::decrypt(base64_encode('short')));
    }

    // ------------------------------------------------------------------
    // hash (deterministic, for indexed lookup)
    // ------------------------------------------------------------------

    public function testHashDeterministic(): void
    {
        $input = 'jan@kowalski.pl';
        $hash1 = Encryption::hash($input);
        $hash2 = Encryption::hash($input);

        $this->assertEquals($hash1, $hash2);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash1, 'Must be hex SHA-256');
    }

    public function testHashDifferentInputs(): void
    {
        $a = Encryption::hash('alice@example.com');
        $b = Encryption::hash('bob@example.com');

        $this->assertNotEquals($a, $b);
    }

    public function testHashCaseInsensitive(): void
    {
        $lower = Encryption::hash('Jan@Test.PL');
        $upper = Encryption::hash('jan@test.pl');

        $this->assertEquals($lower, $upper, 'Hash should lowercase input');
    }

    public function testHashTrimsWhitespace(): void
    {
        $normal  = Encryption::hash('test@email.pl');
        $padded  = Encryption::hash('  test@email.pl  ');

        $this->assertEquals($normal, $padded, 'Hash should trim whitespace');
    }

    // ------------------------------------------------------------------
    // generateKey
    // ------------------------------------------------------------------

    public function testGenerateKeyLength(): void
    {
        $key = Encryption::generateKey();

        // 32 bytes → base64 → 44 chars (with = padding)
        $this->assertEquals(44, strlen($key), 'Key must be 44 chars base64 (32 bytes)');

        // Verify it decodes to exactly 32 bytes
        $decoded = base64_decode($key, true);
        $this->assertNotFalse($decoded);
        $this->assertEquals(32, strlen($decoded));
    }

    public function testGenerateKeyUnique(): void
    {
        $a = Encryption::generateKey();
        $b = Encryption::generateKey();

        $this->assertNotEquals($a, $b, 'Each generated key must be unique');
    }

    // ------------------------------------------------------------------
    // isConfigured
    // ------------------------------------------------------------------

    public function testIsConfiguredReturnsTrueWithKey(): void
    {
        $this->assertTrue(Encryption::isConfigured());
    }
}
