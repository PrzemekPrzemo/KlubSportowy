<?php

namespace App\Helpers;

/**
 * I18n translator (PL/EN) z cascade resolution.
 *
 * Cascade lookup (setLocaleForUser):
 *   1) member.preferred_locale (jesli memberId set i kolumna istnieje)
 *   2) user.preferred_locale  (jesli userId set i kolumna istnieje)
 *   3) session 'locale'
 *   4) club.default_locale    (jesli clubId set i kolumna istnieje)
 *   5) Accept-Language header (en* -> en, else pl)
 *   6) Hard fallback 'pl'
 *
 * Whitelist locale: tylko 'pl' i 'en' — wszystko inne -> 'pl'.
 */
class Translator
{
    public const SUPPORTED = ['pl', 'en'];
    public const FALLBACK  = 'pl';

    private static ?string $locale = null;
    private static array $messages = [];

    /**
     * Wymusza locale na biezacy request. Niedozwolone wartosci -> 'pl'.
     */
    public static function setLocale(string $l): void
    {
        $l = strtolower(trim($l));
        if (!in_array($l, self::SUPPORTED, true)) {
            $l = self::FALLBACK;
        }
        self::$locale = $l;
        self::$messages = [];
    }

    public static function getLocale(): string
    {
        if (self::$locale) {
            return self::$locale;
        }
        $sess = Session::get('locale');
        if (is_string($sess) && in_array($sess, self::SUPPORTED, true)) {
            return self::$locale = $sess;
        }
        // Accept-Language fallback
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return self::$locale = (str_starts_with(strtolower($accept), 'en') ? 'en' : self::FALLBACK);
    }

    /**
     * Cascade resolve + wymuszenie locale dla zalogowanego uzytkownika/czlonka.
     *
     * Best-effort: brak kolumn / brak rekordu -> fallback do kolejnej warstwy.
     * Bezpieczne nawet jesli migracja 098 nie zostala uruchomiona.
     */
    public static function setLocaleForUser(?int $userId, ?int $memberId, ?int $clubId): string
    {
        // 1) member.preferred_locale
        if ($memberId !== null) {
            $loc = self::fetchColumn('SELECT preferred_locale FROM members WHERE id = ? LIMIT 1', [$memberId]);
            if ($loc !== null) {
                self::setLocale($loc);
                Session::set('locale', $loc);
                return $loc;
            }
        }

        // 2) user.preferred_locale (kolumna moze nie istniec; fail-safe)
        if ($userId !== null) {
            $loc = self::fetchColumn('SELECT preferred_locale FROM users WHERE id = ? LIMIT 1', [$userId]);
            if ($loc !== null) {
                self::setLocale($loc);
                Session::set('locale', $loc);
                return $loc;
            }
        }

        // 3) Session locale (np. uzytkownik zmienil ?lang=)
        $sess = Session::get('locale');
        if (is_string($sess) && in_array($sess, self::SUPPORTED, true)) {
            self::setLocale($sess);
            return $sess;
        }

        // 4) club.default_locale
        if ($clubId !== null) {
            $loc = self::fetchColumn('SELECT default_locale FROM clubs WHERE id = ? LIMIT 1', [$clubId]);
            if ($loc !== null) {
                self::setLocale($loc);
                return $loc;
            }
        }

        // 5) Accept-Language + 6) hard fallback
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $loc = str_starts_with(strtolower($accept), 'en') ? 'en' : self::FALLBACK;
        self::setLocale($loc);
        return $loc;
    }

    /**
     * Tymczasowo przelacza locale na czas wykonania callable (np. wysylki
     * bulk emaila dla roznych odbiorcow). NIE zmienia session locale.
     *
     * @template T
     * @param string $locale
     * @param callable():T $fn
     * @return T
     */
    public static function withLocale(string $locale, callable $fn): mixed
    {
        $locale = strtolower(trim($locale));
        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = self::FALLBACK;
        }
        $prev    = self::$locale;
        $prevMsg = self::$messages;
        self::$locale   = $locale;
        self::$messages = [];
        try {
            return $fn();
        } finally {
            self::$locale   = $prev;
            self::$messages = $prevMsg;
        }
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

    /**
     * Best-effort DB fetch — silently null na blad (brak migracji, brak rekordu, brak DB).
     * Waliduje wynik przeciwko SUPPORTED whitelist.
     */
    private static function fetchColumn(string $sql, array $params): ?string
    {
        try {
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            $val = $stmt->fetchColumn();
            if (is_string($val) && in_array($val, self::SUPPORTED, true)) {
                return $val;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}
