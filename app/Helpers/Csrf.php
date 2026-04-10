<?php

namespace App\Helpers;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }
        return Session::get(self::TOKEN_KEY);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . View::e(self::token()) . '">';
    }

    public static function verify(): void
    {
        $submitted = $_POST['_csrf'] ?? '';
        if (!hash_equals(self::token(), $submitted)) {
            http_response_code(419);
            die('Nieprawidłowy token CSRF. Odśwież stronę i spróbuj ponownie.');
        }
    }
}
