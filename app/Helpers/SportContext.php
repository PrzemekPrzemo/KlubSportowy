<?php

namespace App\Helpers;

/**
 * Zarządza aktywną sekcją sportową w sesji.
 *
 * Klub może mieć wiele sekcji sportowych — użytkownik przełącza między
 * nimi (np. zarządzanie sekcją piłkarską vs strzelecką). SportContext
 * przechowuje aktywne club_sport_id (rekord z tabeli club_sports).
 *
 * Wartość null = "wszystkie sporty klubu" (widok ogólnoklubowy).
 */
class SportContext
{
    private const CLUB_SPORT_KEY = 'active_club_sport_id';
    private const SPORT_KEY      = 'active_sport_id';
    private const SPORT_KEY_STR  = 'active_sport_key';

    public static function currentClubSport(): ?int
    {
        $v = Session::get(self::CLUB_SPORT_KEY);
        return $v !== null ? (int)$v : null;
    }

    public static function currentSportId(): ?int
    {
        $v = Session::get(self::SPORT_KEY);
        return $v !== null ? (int)$v : null;
    }

    public static function currentSportKey(): ?string
    {
        return Session::get(self::SPORT_KEY_STR);
    }

    /**
     * Ustawia aktywną sekcję klubową (club_sport_id) wraz z powiązanym
     * sport_id i sport.key. Przekaż null aby wyczyścić kontekst.
     */
    public static function set(?int $clubSportId, ?int $sportId = null, ?string $sportKey = null): void
    {
        if ($clubSportId === null) {
            self::clear();
            return;
        }
        Session::set(self::CLUB_SPORT_KEY, $clubSportId);
        if ($sportId !== null) {
            Session::set(self::SPORT_KEY, $sportId);
        }
        if ($sportKey !== null) {
            Session::set(self::SPORT_KEY_STR, $sportKey);
        }
    }

    public static function clear(): void
    {
        Session::remove(self::CLUB_SPORT_KEY);
        Session::remove(self::SPORT_KEY);
        Session::remove(self::SPORT_KEY_STR);
    }
}
