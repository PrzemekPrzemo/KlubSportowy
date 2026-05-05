<?php

namespace App\Models\Traits;

use App\Helpers\Encryption;

/**
 * Trait dla auto-encrypt / auto-decrypt wybranych pól w modelu.
 *
 * Klasa używająca trait deklaruje statyczną tablicę `$ENCRYPTED_FIELDS`
 * (lub static method encryptedFields()) zawierającą nazwy pól.
 *
 * Przy insert()/update() — wartości w $data są szyfrowane.
 * Przy decoder na fetched rows — `decryptRow()` / `decryptRows()`.
 */
trait EncryptsFields
{
    // Klasy używające trait deklarują wlasne `protected static array $ENCRYPTED_FIELDS = [...]`.
    // PHP 8.4 nie pozwala na property-redeclaration z roznym defaultem, dlatego trait
    // sam nie deklaruje wlasciwosci — uzywa Reflection do bezpiecznego odczytu.

    /** Pobierz listę pól — klasa może nadpisać metodę lub zadeklarować tablicę $ENCRYPTED_FIELDS. */
    protected function encryptedFields(): array
    {
        $cls = static::class;
        try {
            $ref = new \ReflectionClass($cls);
            if ($ref->hasProperty('ENCRYPTED_FIELDS')) {
                $prop = $ref->getProperty('ENCRYPTED_FIELDS');
                if ($prop->isStatic()) {
                    return (array)$prop->getValue();
                }
            }
        } catch (\Throwable) {}
        return [];
    }

    /** Szyfruje wybrane pola w danych przed zapisem. */
    protected function encryptFields(array $data): array
    {
        if (!Encryption::isConfigured()) {
            return $data; // brak klucza — no-op (DEV)
        }
        foreach ($this->encryptedFields() as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = Encryption::encrypt((string)$data[$field]);
            }
        }
        return $data;
    }

    /** Deszyfruje pola w pojedynczym wierszu. */
    protected function decryptRow(?array $row): ?array
    {
        if (!$row || !Encryption::isConfigured()) return $row;
        foreach ($this->encryptedFields() as $field) {
            if (isset($row[$field]) && $row[$field] !== '') {
                $decrypted = Encryption::decrypt($row[$field]);
                $row[$field] = $decrypted ?? $row[$field]; // fallback gdy plaintext
            }
        }
        return $row;
    }

    /** Deszyfruje pola w tablicy wierszy. */
    protected function decryptRows(array $rows): array
    {
        if (empty($rows) || !Encryption::isConfigured()) return $rows;
        foreach ($rows as &$row) {
            $row = $this->decryptRow($row);
        }
        return $rows;
    }
}
