<?php

namespace App\Helpers;

/**
 * Centralna walidacja danych wejściowych.
 *
 * Użycie:
 *   $v = new Validator($_POST, [
 *       'email'      => 'required|email|max:120',
 *       'first_name' => 'required|min:2|max:60',
 *       'pesel'      => 'pesel',
 *       'birth_date' => 'date',
 *       'amount'     => 'required|numeric|min_value:0',
 *       'status'     => 'required|in:aktywny,zawieszony,wykreslony',
 *   ]);
 *   if ($v->fails()) {
 *       // $v->errors() → ['email' => ['Pole email jest wymagane.'], ...]
 *       // $v->firstError() → 'Pole email jest wymagane.'
 *   }
 *   $clean = $v->validated(); // tylko zwalidowane pola
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $validated = [];

    private static array $messages = [
        'required'   => 'Pole :field jest wymagane.',
        'email'      => 'Pole :field musi być prawidłowym adresem e-mail.',
        'min'        => 'Pole :field musi mieć co najmniej :param znaków.',
        'max'        => 'Pole :field nie może przekraczać :param znaków.',
        'min_value'  => 'Pole :field musi być >= :param.',
        'max_value'  => 'Pole :field musi być <= :param.',
        'numeric'    => 'Pole :field musi być liczbą.',
        'integer'    => 'Pole :field musi być liczbą całkowitą.',
        'date'       => 'Pole :field musi być prawidłową datą (YYYY-MM-DD).',
        'in'         => 'Pole :field musi być jednym z: :param.',
        'pesel'      => 'Pole :field musi być prawidłowym numerem PESEL.',
        'phone'      => 'Pole :field musi być prawidłowym numerem telefonu.',
        'url'        => 'Pole :field musi być prawidłowym adresem URL.',
        'regex'      => 'Pole :field ma nieprawidłowy format.',
        'confirmed'  => 'Pole :field nie zgadza się z potwierdzeniem.',
        'unique'     => 'Wartość :field jest już zajęta.',
        'min_length' => 'Pole :field musi mieć co najmniej :param znaków.',
        'max_length' => 'Pole :field nie może przekraczać :param znaków.',
    ];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    public function allErrors(): array
    {
        $all = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $e) { $all[] = $e; }
        }
        return $all;
    }

    /** Zwraca tablicę tylko zwalidowanych pól (klucze zgodne z rules). */
    public function validated(): array
    {
        return $this->validated;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $trimmed = is_string($value) ? trim($value) : $value;

            $isRequired = in_array('required', $rules, true);
            $isEmpty    = $trimmed === '' || $trimmed === null;

            if (!$isRequired && $isEmpty) {
                $this->validated[$field] = null;
                continue;
            }

            foreach ($rules as $rule) {
                $param = null;
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $ok = match ($rule) {
                    'required'   => !$isEmpty,
                    'email'      => !$isEmpty && filter_var($trimmed, FILTER_VALIDATE_EMAIL) !== false,
                    'numeric'    => !$isEmpty && is_numeric($trimmed),
                    'integer'    => !$isEmpty && ctype_digit(ltrim((string)$trimmed, '-')),
                    'min'        => !$isEmpty && mb_strlen((string)$trimmed) >= (int)$param,
                    'max'        => $isEmpty || mb_strlen((string)$trimmed) <= (int)$param,
                    'min_length' => !$isEmpty && mb_strlen((string)$trimmed) >= (int)$param,
                    'max_length' => $isEmpty || mb_strlen((string)$trimmed) <= (int)$param,
                    'min_value'  => !$isEmpty && is_numeric($trimmed) && (float)$trimmed >= (float)$param,
                    'max_value'  => $isEmpty || (is_numeric($trimmed) && (float)$trimmed <= (float)$param),
                    'date'       => !$isEmpty && (bool)\DateTime::createFromFormat('Y-m-d', (string)$trimmed),
                    'in'         => !$isEmpty && in_array($trimmed, explode(',', $param ?? ''), true),
                    'url'        => !$isEmpty && filter_var($trimmed, FILTER_VALIDATE_URL) !== false,
                    'regex'      => !$isEmpty && preg_match($param ?? '//', (string)$trimmed) === 1,
                    'confirmed'  => $trimmed === ($this->data[$field . '_confirmation'] ?? $this->data[$field . '2'] ?? null),
                    'pesel'      => $isEmpty || self::validatePesel((string)$trimmed),
                    'phone'      => $isEmpty || (bool)preg_match('/^\+?[\d\s\-]{7,20}$/', (string)$trimmed),
                    default      => true,
                };

                if (!$ok) {
                    $msg = str_replace(
                        [':field', ':param'],
                        [$field, $param ?? ''],
                        self::$messages[$rule] ?? "Pole :field nie spełnia reguły {$rule}."
                    );
                    $this->errors[$field][] = $msg;
                    break; // jedna błędna reguła per pole
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $trimmed;
            }
        }
    }

    /** Walidacja numeru PESEL (suma kontrolna). */
    private static function validatePesel(string $pesel): bool
    {
        if (!preg_match('/^\d{11}$/', $pesel)) return false;
        $weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int)$pesel[$i] * $weights[$i];
        }
        $check = (10 - ($sum % 10)) % 10;
        return $check === (int)$pesel[10];
    }

    /**
     * Helper: waliduj i flash error jeśli nie przechodzi.
     * Zwraca validated data lub null + redirect.
     */
    public static function validateOrFlash(array $data, array $rules, string $redirectPath): ?array
    {
        $v = new self($data, $rules);
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            Session::flash('_old_input', $data);
            header('Location: ' . url($redirectPath));
            exit;
        }
        return $v->validated();
    }
}
