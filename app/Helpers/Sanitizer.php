<?php

namespace App\Helpers;

/**
 * Centralna sanityzacja danych wejściowych.
 *
 * Używaj ZAMIAST surowego $_POST/$_GET w controllerach.
 * Validator waliduje reguły, Sanitizer czyści dane.
 */
class Sanitizer
{
    /** Usuwa tagi HTML, trim, NUL bytes. */
    public static function clean(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $v = (string)$value;
        $v = str_replace("\0", '', $v);   // NUL byte
        $v = strip_tags($v);
        return trim($v);
    }

    /** Sanityzacja HTML — zachowuje bezpieczne tagi (b, i, br, p, ul, li). */
    public static function sanitizeHtml(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $v = str_replace("\0", '', (string)$value);
        return trim(strip_tags($v, '<b><i><br><p><ul><ol><li><strong><em><a><h1><h2><h3><h4><h5><h6>'));
    }

    /** Bezpieczna nazwa pliku — alfanumeryczne + myślniki + kropka. */
    public static function sanitizeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        $ext  = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        return $name . ($ext ? '.' . $ext : '');
    }

    /** Wymusza int lub null. */
    public static function sanitizeInt(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        return (int)$value;
    }

    /** Wymusza float lub null. */
    public static function sanitizeFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        return (float)$value;
    }

    /** Sanityzacja email — lowercase + trim. */
    public static function sanitizeEmail(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $v = strtolower(trim((string)$value));
        return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    /** Sanityzacja tablicy POST — clean każdego klucza. */
    public static function post(string $key, mixed $default = null): mixed
    {
        $val = $_POST[$key] ?? $default;
        if (is_string($val)) return self::clean($val);
        return $val;
    }

    /** Sanityzacja parametru GET. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $val = $_GET[$key] ?? $default;
        if (is_string($val)) return self::clean($val);
        return $val;
    }

    /** Batch clean array (np. cała $_POST). */
    public static function cleanArray(array $data, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = isset($data[$key]) ? self::clean($data[$key]) : null;
        }
        return $result;
    }
}
