<?php

namespace App\Helpers;

use App\Models\ClubModel;
use App\Models\ReferralCodeModel;

/**
 * Generuje i waliduje kody referral typu `KLUB-AB12CD`.
 *
 * Format: <prefix-z-nazwy-klubu, max 6 znakow>-<6 losowych alfanumerycznych>.
 * Lazy creation: pierwsze wejscie na /club/referrals tworzy kod
 * jesli klub jeszcze go nie ma.
 */
final class ReferralCodeService
{
    private const MAX_GENERATE_ATTEMPTS = 8;

    /** Zwraca istniejacy aktywny kod lub generuje nowy. */
    public static function ensureForClub(int $clubId): string
    {
        $model = new ReferralCodeModel();
        $existing = $model->findForClub($clubId);
        if ($existing !== null) {
            return (string)$existing['code'];
        }
        return self::generateForClub($clubId);
    }

    public static function generateForClub(int $clubId): string
    {
        $model = new ReferralCodeModel();
        // Deaktywuj poprzednie kody klubu (zachowujemy historyczne rekordy).
        $model->deactivateForClub($clubId);

        $prefix = self::prefixForClub($clubId);

        for ($i = 0; $i < self::MAX_GENERATE_ATTEMPTS; $i++) {
            $code = $prefix . '-' . self::randomSuffix(6);
            if ($model->findByCode($code) === null) {
                $model->insert([
                    'club_id'   => $clubId,
                    'code'      => $code,
                    'is_active' => 1,
                ]);
                return $code;
            }
        }
        // Fallback: bardzo niska szansa kolizji — uzyj dluzszego suffixu.
        $code = $prefix . '-' . self::randomSuffix(10);
        $model->insert([
            'club_id'   => $clubId,
            'code'      => $code,
            'is_active' => 1,
        ]);
        return $code;
    }

    /** Walidacja kodu wpisanego przez polecanego: aktywny + nie self-referral. */
    public static function validateForReferred(string $code, ?int $referredClubId = null): ?array
    {
        $code = self::normalize($code);
        if ($code === '') {
            return null;
        }
        $model = new ReferralCodeModel();
        $row = $model->findActiveByCode($code);
        if ($row === null) {
            return null;
        }
        if ($referredClubId !== null && (int)$row['club_id'] === $referredClubId) {
            return null; // self-referral
        }
        return $row;
    }

    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    private static function prefixForClub(int $clubId): string
    {
        try {
            $club = (new ClubModel())->findById($clubId);
        } catch (\Throwable) {
            $club = null;
        }
        $name = $club['name'] ?? 'KLUB';
        // Usun wszystko poza A-Z + cyfry (after uppercase + transliteracja)
        $ascii = self::asciiUpper($name);
        $ascii = preg_replace('/[^A-Z0-9]+/', '', $ascii) ?? 'KLUB';
        if ($ascii === '') {
            $ascii = 'KLUB';
        }
        return substr($ascii, 0, 6);
    }

    private static function asciiUpper(string $s): string
    {
        $map = [
            'ą'=>'A','ć'=>'C','ę'=>'E','ł'=>'L','ń'=>'N','ó'=>'O',
            'ś'=>'S','ż'=>'Z','ź'=>'Z',
            'Ą'=>'A','Ć'=>'C','Ę'=>'E','Ł'=>'L','Ń'=>'N','Ó'=>'O',
            'Ś'=>'S','Ż'=>'Z','Ź'=>'Z',
        ];
        $s = strtr($s, $map);
        return mb_strtoupper($s, 'UTF-8');
    }

    private static function randomSuffix(int $len): string
    {
        // Bez ambiguous: 0/O, 1/I/L
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
