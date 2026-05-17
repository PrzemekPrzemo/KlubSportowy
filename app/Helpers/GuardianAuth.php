<?php

namespace App\Helpers;

use App\Models\GuardianModel;

/**
 * Autoryzacja opiekuna w portalu rodzica (RODO art. 8).
 *
 * Oddzielona od:
 *   - Auth          (admini klubu)
 *   - MemberAuth    (zawodnicy w portalu zawodnika)
 *
 * Sesja:
 *   guardian_auth_id          — guardian.id
 *   guardian_auth_email       — login
 *   guardian_auth_name        — display
 *   guardian_auth_club_id     — aktywny klub (tenant)
 */
class GuardianAuth
{
    private const SK_ID    = 'guardian_auth_id';
    private const SK_EMAIL = 'guardian_auth_email';
    private const SK_NAME  = 'guardian_auth_name';
    private const SK_CLUB  = 'guardian_auth_club_id';

    public static function check(): bool
    {
        return Session::has(self::SK_ID);
    }

    public static function id(): ?int
    {
        $v = Session::get(self::SK_ID);
        return $v !== null ? (int)$v : null;
    }

    public static function clubId(): ?int
    {
        $v = Session::get(self::SK_CLUB);
        return $v !== null ? (int)$v : null;
    }

    public static function email(): ?string
    {
        return Session::get(self::SK_EMAIL);
    }

    public static function name(): ?string
    {
        return Session::get(self::SK_NAME);
    }

    public static function current(): ?array
    {
        $id = self::id();
        if ($id === null) return null;
        // withoutScope: nie chcemy zaleznosci od ClubContext (zostal ustawiony
        // przez login, ale moze nie byc jeszcze zsynchronizowany)
        return (new GuardianModel())->withoutScope()->findById($id);
    }

    /**
     * Loguje opiekuna do sesji. Ustawia rowniez ClubContext (multi-tenant scope).
     */
    public static function login(array $guardian): void
    {
        Session::start();
        session_regenerate_id(true);

        Session::set(self::SK_ID,    (int)$guardian['id']);
        Session::set(self::SK_EMAIL, (string)$guardian['email']);
        Session::set(self::SK_CLUB,  (int)$guardian['club_id']);

        $name = trim(
            (string)($guardian['first_name'] ?? '') . ' ' .
            (string)($guardian['last_name']  ?? '')
        );
        if ($name === '') $name = (string)$guardian['email'];
        Session::set(self::SK_NAME, $name);

        ClubContext::set((int)$guardian['club_id']);
    }

    public static function logout(): void
    {
        Session::remove(self::SK_ID);
        Session::remove(self::SK_EMAIL);
        Session::remove(self::SK_NAME);
        Session::remove(self::SK_CLUB);
        ClubContext::clear();
    }

    /**
     * Wymusza logowanie — przekierowuje do /guardian/login gdy brak sesji.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('warning', 'Zaloguj sie aby uzyc portalu opiekuna.');
            header('Location: ' . url('guardian/login'));
            exit;
        }
        if (ClubContext::current() === null) {
            $clubId = self::clubId();
            if ($clubId !== null) {
                ClubContext::set($clubId);
            }
        }
    }
}
