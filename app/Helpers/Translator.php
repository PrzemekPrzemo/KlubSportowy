<?php

namespace App\Helpers;

class Translator
{
    private static ?string $locale = null;
    private static array $messages = [];

    public static function setLocale(string $l): void
    {
        self::$locale = $l;
        self::$messages = [];
    }

    public static function getLocale(): string
    {
        if (self::$locale) {
            return self::$locale;
        }
        $sess = Session::get('locale');
        if ($sess) {
            return self::$locale = $sess;
        }
        // Accept-Language fallback
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return self::$locale = (str_starts_with($accept, 'en') ? 'en' : 'pl');
    }

    public static function t(string $key, array $params = []): string
    {
        self::loadMessages();
        $msg = self::$messages[$key] ?? $key;
        foreach ($params as $k => $v) {
            $msg = str_replace(':' . $k, (string)$v, $msg);
        }
        return $msg;
    }

    private static function loadMessages(): void
    {
        if (!empty(self::$messages)) {
            return;
        }
        $locale = self::getLocale();
        $file = ROOT_PATH . '/lang/' . $locale . '/messages.php';
        self::$messages = file_exists($file) ? require $file : [];
    }
}
