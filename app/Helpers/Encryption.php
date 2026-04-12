<?php

namespace App\Helpers;

use RuntimeException;

/**
 * AES-256-GCM szyfrowanie danych wrażliwych.
 *
 * Szyfrowane kolumny: members.pesel, members.email, members.phone,
 * club_settings wartości haseł (smtp_pass, api keys).
 *
 * Format ciphertext: base64( nonce[12] . ciphertext . tag[16] )
 * Hash (do wyszukiwania): SHA-256 lowercase(value) → hex 64 chars
 */
class Encryption
{
    private const CIPHER = 'aes-256-gcm';
    private const NONCE_LENGTH = 12;
    private const TAG_LENGTH = 16;

    private static ?string $key = null;

    /** Szyfruje wartość. Null → null. */
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') return null;

        $key = self::getKey();
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $value, self::CIPHER, $key,
            OPENSSL_RAW_DATA, $nonce, $tag, '', self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return base64_encode($nonce . $ciphertext . $tag);
    }

    /** Deszyfruje wartość. Null → null. */
    public static function decrypt(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') return null;

        $key = self::getKey();
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) < self::NONCE_LENGTH + self::TAG_LENGTH + 1) {
            return null; // nieprawidłowy format — zwróć null zamiast crash
        }

        $nonce      = substr($raw, 0, self::NONCE_LENGTH);
        $tag        = substr($raw, -self::TAG_LENGTH);
        $ciphertext = substr($raw, self::NONCE_LENGTH, -self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext, self::CIPHER, $key,
            OPENSSL_RAW_DATA, $nonce, $tag
        );

        if ($plaintext === false) {
            return null; // tampered data lub zły klucz
        }

        return $plaintext;
    }

    /**
     * Hash wartości do wyszukiwania (indeksowana kolumna _hash).
     * SHA-256 na lowercase trimmed value → hex 64 chars.
     * Deterministyczny — ten sam input = ten sam hash.
     */
    public static function hash(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }

    /** Generuje nowy klucz szyfrowania (32 bajty, base64). */
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /** Sprawdza czy szyfrowanie jest skonfigurowane (klucz ustawiony). */
    public static function isConfigured(): bool
    {
        try {
            self::getKey();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    private static function getKey(): string
    {
        if (self::$key !== null) return self::$key;

        $localConfig = ROOT_PATH . '/config/encryption.local.php';
        $config = file_exists($localConfig)
            ? require $localConfig
            : require ROOT_PATH . '/config/encryption.php';

        $keyBase64 = $config['key'] ?? '';
        if ($keyBase64 === '') {
            throw new RuntimeException(
                'Encryption key not configured. Run: php cli/generate-key.php'
            );
        }

        $key = base64_decode($keyBase64, true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Invalid encryption key (must be 32 bytes base64).');
        }

        return self::$key = $key;
    }

    /** Reset cache — do testów. */
    public static function reset(): void
    {
        self::$key = null;
    }
}
