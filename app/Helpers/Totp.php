<?php

namespace App\Helpers;

/**
 * TOTP (RFC 6238) — zgodny z Google Authenticator / Authy.
 * Czysta implementacja bez zewnętrznych bibliotek.
 */
class Totp
{
    private const DIGITS = 6;
    private const PERIOD = 30;

    /** Generuje nowy losowy sekret w Base32. */
    public static function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        return self::base32Encode($bytes);
    }

    /** Zwraca aktualny kod TOTP dla danego sekretu. */
    public static function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter   = intdiv($timestamp, self::PERIOD);
        return self::hotp($secret, $counter);
    }

    /**
     * Weryfikuje kod użytkownika. Dopuszcza +/- 1 okno czasowe
     * aby zniwelować drift zegara.
     */
    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = time();
        $counter   = intdiv($timestamp, self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::hotp($secret, $counter + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /** Zwraca URL otpauth:// do wyświetlenia jako QR (np. qrcode.js). */
    public static function otpauthUrl(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/'
            . rawurlencode($issuer . ':' . $label)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    /** HOTP — używane pod spodem TOTP. */
    private static function hotp(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        $bin = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        $code = (
            ((ord($hash[$offset])   & 0x7f) << 24) |
            ((ord($hash[$offset+1]) & 0xff) << 16) |
            ((ord($hash[$offset+2]) & 0xff) << 8)  |
            ( ord($hash[$offset+3]) & 0xff)
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $bytes): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary   = '';
        foreach (str_split($bytes) as $ch) {
            $binary .= str_pad(decbin(ord($ch)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out  .= $alphabet[bindec($chunk)];
        }
        return $out;
    }

    private static function base32Decode(string $encoded): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded  = strtoupper($encoded);
        $binary   = '';
        foreach (str_split($encoded) as $ch) {
            $pos = strpos($alphabet, $ch);
            if ($pos === false) continue;
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }

    /** Generuje N jednorazowych kodów zapasowych. */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
        }
        return $codes;
    }
}
