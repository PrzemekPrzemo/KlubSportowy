<?php

namespace App\Helpers;

/**
 * Trait for controllers — provides defensive request validation.
 *
 * Każda metoda zwraca walidowaną wartość lub flashuje błąd + redirect na
 * podany URL. Jednolity wzorzec dla wszystkich plugin-controllerów.
 *
 * Przykład użycia:
 *   $memberId = $this->validateInt($_POST['member_id'] ?? '', 'member_id', 1, null, 'sport/results');
 *   $name     = $this->validateString($_POST['name'] ?? '', 'name', 1, 200, 'sport/results');
 *   $stroke   = $this->validateInList($_POST['stroke'] ?? '', SwimmingResult::$STROKES, 'stroke', 'sport/results');
 *   $date     = $this->validateDate($_POST['date'] ?? '', 'date', 'sport/results');
 *
 * Po pierwszym błędzie metoda flashuje + redirect — controller nie musi
 * sprawdzać każdego pola w if/else.
 */
trait ValidatesRequest
{
    protected function validateInt(
        mixed $value,
        string $field,
        ?int $min = null,
        ?int $max = null,
        string $redirectTo = '/'
    ): int {
        if ($value === '' || $value === null) {
            $this->validationFail("Pole '{$field}' jest wymagane.", $redirectTo);
        }
        if (!is_numeric($value)) {
            $this->validationFail("Pole '{$field}' musi być liczbą.", $redirectTo);
        }
        $int = (int)$value;
        if ($min !== null && $int < $min) {
            $this->validationFail("Pole '{$field}' musi być >= {$min}.", $redirectTo);
        }
        if ($max !== null && $int > $max) {
            $this->validationFail("Pole '{$field}' musi być <= {$max}.", $redirectTo);
        }
        return $int;
    }

    protected function validateString(
        mixed $value,
        string $field,
        int $minLen = 1,
        int $maxLen = 255,
        string $redirectTo = '/'
    ): string {
        $str = is_string($value) ? trim($value) : '';
        $len = mb_strlen($str);
        if ($len < $minLen) {
            $this->validationFail("Pole '{$field}' jest wymagane (min {$minLen} znaków).", $redirectTo);
        }
        if ($len > $maxLen) {
            $this->validationFail("Pole '{$field}' przekracza limit {$maxLen} znaków.", $redirectTo);
        }
        return $str;
    }

    /** Returns trimmed string or null if empty (for nullable VARCHAR columns). */
    protected function validateOptionalString(mixed $value, int $maxLen = 255, string $redirectTo = '/'): ?string
    {
        $str = is_string($value) ? trim($value) : '';
        if ($str === '') return null;
        if (mb_strlen($str) > $maxLen) {
            $this->validationFail("Wartość przekracza limit {$maxLen} znaków.", $redirectTo);
        }
        return $str;
    }

    /**
     * Walidacja klucza ENUM przeciw whitelist (np. WrestlingResultModel::$STYLES).
     * @param array<string,mixed>|string[] $allowed
     */
    protected function validateInList(
        mixed $value,
        array $allowed,
        string $field,
        string $redirectTo = '/'
    ): string {
        $str = is_string($value) ? $value : '';
        // Pozwol kluczom asocjacyjnym (e.g. 'freestyle' => 'Wolnoamerykański')
        // lub flat values.
        $keys   = array_keys($allowed);
        $values = array_values($allowed);
        $set    = array_merge($keys, $values);
        if (!in_array($str, $set, true)) {
            $this->validationFail("Pole '{$field}' ma niedozwoloną wartość.", $redirectTo);
        }
        return $str;
    }

    protected function validateDate(mixed $value, string $field, string $redirectTo = '/'): string
    {
        $str = is_string($value) ? trim($value) : '';
        if ($str === '') {
            $this->validationFail("Pole '{$field}' jest wymagane.", $redirectTo);
        }
        $d = \DateTime::createFromFormat('Y-m-d', $str);
        if (!$d || $d->format('Y-m-d') !== $str) {
            $this->validationFail("Pole '{$field}' musi być datą w formacie YYYY-MM-DD.", $redirectTo);
        }
        return $str;
    }

    protected function validateOptionalInt(mixed $value, ?int $min = null, ?int $max = null, string $redirectTo = '/'): ?int
    {
        if ($value === '' || $value === null) return null;
        return $this->validateInt($value, 'value', $min, $max, $redirectTo);
    }

    /**
     * Pomocnicze: rzuca SessionFlash + redirect.
     * Controller-specific use: zwraca void (redirect przerywa).
     */
    private function validationFail(string $msg, string $redirectTo): never
    {
        Session::flash('error', $msg);
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        header('Location: ' . $base . '/' . ltrim($redirectTo, '/'));
        exit;
    }
}
