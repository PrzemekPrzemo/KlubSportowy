<?php

namespace App\Helpers;

/**
 * Defensywny helper do zapisywania uploadowanych logo.
 *
 * Obsługuje wszystkie 3 ścieżki upload-u (system / klub / sekcja sportowa).
 * Skupia logikę walidacji + bezpieczne move_uploaded_file w jednym miejscu —
 * defensywne, żeby nie wybuchać ErrorException-em (set_error_handler w
 * public/index.php konwertuje warningi na exception-y).
 *
 * Konkretne źródła błędów które obsługujemy:
 *   - Brak katalogu docelowego (mkdir fail przez uprawnienia parent-a)
 *   - $_FILES error code != UPLOAD_ERR_OK
 *   - move_uploaded_file warning gdy destination niezapisywalny
 *   - is_uploaded_file false (próba HTTP injection / podstawienie ścieżki)
 *
 * Wszystkie błędy wystawiają user-friendly flash + zwracają null.
 */
class LogoUploader
{
    public const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
    public const MAX_SIZE    = 2 * 1024 * 1024; // 2 MB

    /**
     * Zapisz uploadowany plik logo do katalogu docelowego.
     *
     * @param array  $file      pojedynczy element z $_FILES (tmp_name/name/size/error)
     * @param string $absDir    bezwzględna ścieżka do docelowego katalogu (utworzy jeśli brak)
     * @param string $relPrefix prefiks ścieżki względnej do public/ (np. "uploads/system")
     * @param string $variant   etykieta wariantu (np. "color", "main", "alt", "dark")
     * @return string|null      ścieżka względna do public/ (do zapisu w DB), albo null on fail
     */
    public static function save(array $file, string $absDir, string $relPrefix, string $variant): ?string
    {
        // 1. Sprawdź $_FILES error code
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return null; // user nie wybrał pliku — nie błąd
        }
        if ($err !== UPLOAD_ERR_OK) {
            Session::flash('error', self::uploadErrorMessage($err));
            return null;
        }

        // 2. Walidacja rozszerzenia
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            Session::flash('error', 'Niedozwolone rozszerzenie pliku — dozwolone: '
                . implode(', ', self::ALLOWED_EXT) . '.');
            return null;
        }

        // 3. Walidacja rozmiaru
        if (((int)($file['size'] ?? 0)) > self::MAX_SIZE) {
            Session::flash('error', 'Plik logo musi być mniejszy niż 2 MB.');
            return null;
        }

        // 4. Tylko prawdziwy upload (chroni przed prób podstawienia ścieżki)
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            Session::flash('error', 'Nieprawidłowy upload pliku.');
            return null;
        }

        // 5. Utwórz katalog z jawnym sprawdzeniem (bez @ — chcemy diagnostykę)
        if (!is_dir($absDir)) {
            // Suppress warning żeby set_error_handler nie threw exception-a;
            // sami sprawdzamy result i logujemy przyczynę.
            if (!@mkdir($absDir, 0775, true) && !is_dir($absDir)) {
                $parent = dirname($absDir);
                $parentInfo = is_dir($parent)
                    ? (is_writable($parent) ? 'writable' : 'NOT writable')
                    : 'does not exist';
                error_log("LogoUploader::save mkdir failed: {$absDir} (parent {$parent}: {$parentInfo})");
                Session::flash('error',
                    'Nie udało się utworzyć katalogu na pliki. Skontaktuj się z administratorem '
                    . '(sprawdź uprawnienia ' . htmlspecialchars(dirname($absDir)) . ').'
                );
                return null;
            }
        }
        if (!is_writable($absDir)) {
            error_log("LogoUploader::save dir not writable: {$absDir}");
            Session::flash('error',
                'Katalog docelowy nie jest zapisywalny. Skontaktuj się z administratorem.'
            );
            return null;
        }

        // 6. Zapis pliku — @ żeby ewentualny warning nie throwed jako exception
        $filename = "logo_{$variant}_" . time() . '.' . $ext;
        $absPath  = $absDir . '/' . $filename;
        if (!@move_uploaded_file($file['tmp_name'], $absPath)) {
            error_log("LogoUploader::save move_uploaded_file failed: {$file['tmp_name']} -> {$absPath}");
            Session::flash('error', 'Nie udało się zapisać pliku logo na dysku.');
            return null;
        }

        // 7. Zwróć ścieżkę względną do public/
        return rtrim($relPrefix, '/') . '/' . $filename;
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'Plik za duży (limit serwera).',
            UPLOAD_ERR_FORM_SIZE  => 'Plik za duży (limit formularza).',
            UPLOAD_ERR_PARTIAL    => 'Plik został wgrany tylko częściowo. Spróbuj ponownie.',
            UPLOAD_ERR_NO_TMP_DIR => 'Brak tymczasowego katalogu serwera.',
            UPLOAD_ERR_CANT_WRITE => 'Nie udało się zapisać pliku tymczasowego.',
            UPLOAD_ERR_EXTENSION  => 'Upload zablokowany przez rozszerzenie PHP.',
            default               => 'Nieznany błąd uploadu (kod ' . $code . ').',
        };
    }
}
