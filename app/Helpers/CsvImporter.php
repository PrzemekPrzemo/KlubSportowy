<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\MemberModel;

class CsvImporter
{
    /** DB columns that can be mapped from CSV. */
    private const DB_COLUMNS = [
        'first_name', 'last_name', 'email', 'pesel', 'birth_date',
        'phone', 'gender', 'address_street', 'address_city',
        'address_postal', 'member_number', 'join_date', 'status', 'notes',
    ];

    /** Common CSV header labels → DB column mapping (Polish + English). */
    private const HEADER_MAP = [
        // first_name
        'imie'        => 'first_name',
        'imię'        => 'first_name',
        'first_name'  => 'first_name',
        'firstname'   => 'first_name',
        'name'        => 'first_name',
        // last_name
        'nazwisko'    => 'last_name',
        'last_name'   => 'last_name',
        'lastname'    => 'last_name',
        'surname'     => 'last_name',
        // email
        'email'       => 'email',
        'e-mail'      => 'email',
        'mail'        => 'email',
        // pesel
        'pesel'       => 'pesel',
        // birth_date
        'data_urodzenia'  => 'birth_date',
        'data urodzenia'  => 'birth_date',
        'birth_date'      => 'birth_date',
        'birthdate'       => 'birth_date',
        'urodzony'        => 'birth_date',
        // phone
        'telefon'     => 'phone',
        'phone'       => 'phone',
        'tel'         => 'phone',
        'nr_telefonu' => 'phone',
        // gender
        'plec'        => 'gender',
        'płeć'        => 'gender',
        'gender'      => 'gender',
        // address
        'ulica'           => 'address_street',
        'address_street'  => 'address_street',
        'adres'           => 'address_street',
        'street'          => 'address_street',
        'miasto'          => 'address_city',
        'address_city'    => 'address_city',
        'city'            => 'address_city',
        'kod_pocztowy'    => 'address_postal',
        'kod pocztowy'    => 'address_postal',
        'address_postal'  => 'address_postal',
        'postal'          => 'address_postal',
        'zip'             => 'address_postal',
        // member_number
        'numer'           => 'member_number',
        'nr'              => 'member_number',
        'member_number'   => 'member_number',
        'nr_czlonkowski'  => 'member_number',
        'nr członkowski'  => 'member_number',
        // join_date
        'data_dolaczenia' => 'join_date',
        'data dołączenia' => 'join_date',
        'join_date'       => 'join_date',
        'dolaczenie'      => 'join_date',
        // status
        'status'          => 'status',
        // notes
        'uwagi'           => 'notes',
        'notatki'         => 'notes',
        'notes'           => 'notes',
    ];

    /**
     * Auto-detect CSV delimiter from the first line of a file.
     */
    public static function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ';';
        }
        $line = fgets($handle);
        fclose($handle);

        if ($line === false) {
            return ';';
        }

        $delimiters = [';' => 0, ',' => 0, "\t" => 0];
        foreach (array_keys($delimiters) as $d) {
            $delimiters[$d] = substr_count($line, $d);
        }

        arsort($delimiters);
        $best = array_key_first($delimiters);

        return $delimiters[$best] > 0 ? $best : ';';
    }

    /**
     * Parse a CSV file into headers + rows.
     *
     * @return array{headers: string[], rows: array[]}
     */
    public static function parse(string $filePath, string $delimiter = ';'): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['headers' => [], 'rows' => []];
        }

        // Skip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }

        // Trim headers
        $headers = array_map('trim', $headers);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Skip fully empty rows
            $filtered = array_filter($row, fn($v) => trim((string)$v) !== '');
            if (empty($filtered)) {
                continue;
            }
            $rows[] = array_map('trim', $row);
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Suggest mapping from CSV headers to DB columns.
     *
     * @param  string[] $csvHeaders
     * @return array<string, string|null>  CSV header => DB column (or null)
     */
    public static function mapColumns(array $csvHeaders): array
    {
        $mapping = [];
        foreach ($csvHeaders as $header) {
            $normalized = mb_strtolower(trim($header));
            $normalized = preg_replace('/[\s\-]+/', '_', $normalized);
            // Remove Polish diacritics for matching
            $ascii = self::removeDiacritics($normalized);

            $mapping[$header] = self::HEADER_MAP[$normalized]
                ?? self::HEADER_MAP[$ascii]
                ?? (in_array($normalized, self::DB_COLUMNS, true) ? $normalized : null);
        }
        return $mapping;
    }

    /**
     * Import rows into the members table.
     *
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public static function import(int $clubId, array $rows, array $mapping, int $userId): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $model = new MemberModel();
        $db    = Database::pdo();

        // Pre-fetch existing member_numbers for this club to detect duplicates
        $stmt = $db->prepare("SELECT member_number FROM members WHERE club_id = ?");
        $stmt->execute([$clubId]);
        $existingNumbers = array_column($stmt->fetchAll(), 'member_number');
        $existingNumbers = array_flip($existingNumbers);

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2; // +2 = 1-based + header row
            try {
                $data = self::buildMemberData($row, $mapping, $clubId, $userId);

                if ($data === null) {
                    $skipped++;
                    $errors[] = "Wiersz {$rowNum}: brak wymaganych danych (imię lub nazwisko).";
                    continue;
                }

                // Validate key fields
                $rowErrors = self::validateRow($data, $rowNum);
                if (!empty($rowErrors)) {
                    $skipped++;
                    $errors = array_merge($errors, $rowErrors);
                    continue;
                }

                // Check duplicate member_number
                $memberNum = $data['member_number'] ?? '';
                if ($memberNum !== '' && isset($existingNumbers[$memberNum])) {
                    $skipped++;
                    $errors[] = "Wiersz {$rowNum}: numer członkowski '{$memberNum}' już istnieje — pominięto.";
                    continue;
                }

                // Auto-generate member_number if empty
                if ($memberNum === '' || $memberNum === null) {
                    $data['member_number'] = $model->nextMemberNumber($clubId);
                }

                $model->insert($data);

                // Track the new number so duplicates within the same import are caught
                if (!empty($data['member_number'])) {
                    $existingNumbers[$data['member_number']] = true;
                }

                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Wiersz {$rowNum}: " . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @return string[] List of DB columns available for mapping.
     */
    public static function dbColumns(): array
    {
        return self::DB_COLUMNS;
    }

    // ──────────────────────────────────────────────────────────

    private static function buildMemberData(array $row, array $mapping, int $clubId, int $userId): ?array
    {
        $data = [];
        foreach ($mapping as $csvIndex => $dbColumn) {
            if ($dbColumn === null || $dbColumn === '' || $dbColumn === '--') {
                continue;
            }
            $data[$dbColumn] = $row[$csvIndex] ?? null;
        }

        // first_name + last_name required
        if (empty(trim($data['first_name'] ?? ''))) {
            return null;
        }
        if (empty(trim($data['last_name'] ?? ''))) {
            return null;
        }

        // Normalize gender
        if (isset($data['gender'])) {
            $g = mb_strtoupper(trim($data['gender']));
            if (in_array($g, ['M', 'K'], true)) {
                $data['gender'] = $g;
            } elseif (in_array($g, ['MĘŻCZYZNA', 'MEZCZYZNA', 'MALE', 'MAN'], true)) {
                $data['gender'] = 'M';
            } elseif (in_array($g, ['KOBIETA', 'FEMALE', 'WOMAN'], true)) {
                $data['gender'] = 'K';
            } else {
                $data['gender'] = null;
            }
        }

        // Normalize status
        if (isset($data['status'])) {
            $s = mb_strtolower(trim($data['status']));
            if (!in_array($s, ['aktywny', 'zawieszony', 'wykreslony', 'urlop'], true)) {
                $data['status'] = 'aktywny';
            } else {
                $data['status'] = $s;
            }
        } else {
            $data['status'] = 'aktywny';
        }

        // Default join_date
        if (empty($data['join_date'])) {
            $data['join_date'] = date('Y-m-d');
        }

        // Trim all strings, convert empty strings to null
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
                $data[$k] = $v !== '' ? $v : null;
            }
        }

        $data['club_id']    = $clubId;
        $data['created_by'] = $userId;

        return $data;
    }

    /**
     * Validate a single row data using Validator rules.
     *
     * @return string[]
     */
    private static function validateRow(array $data, int $rowNum): array
    {
        $rules = [];
        $toValidate = [];

        if (!empty($data['email'])) {
            $rules['email'] = 'email';
            $toValidate['email'] = $data['email'];
        }

        if (!empty($data['pesel'])) {
            $rules['pesel'] = 'pesel';
            $toValidate['pesel'] = $data['pesel'];
        }

        if (!empty($data['birth_date'])) {
            $rules['birth_date'] = 'date';
            $toValidate['birth_date'] = $data['birth_date'];
        }

        if (!empty($data['join_date']) && $data['join_date'] !== date('Y-m-d')) {
            $rules['join_date'] = 'date';
            $toValidate['join_date'] = $data['join_date'];
        }

        if (empty($rules)) {
            return [];
        }

        $v = new Validator($toValidate, $rules);
        if ($v->fails()) {
            $errs = [];
            foreach ($v->allErrors() as $msg) {
                $errs[] = "Wiersz {$rowNum}: {$msg}";
            }
            return $errs;
        }

        return [];
    }

    private static function removeDiacritics(string $str): string
    {
        $map = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
            'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ];
        return strtr($str, $map);
    }
}
