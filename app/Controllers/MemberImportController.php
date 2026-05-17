<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\CsvImporter;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\TenantAccessLogModel;

/**
 * Dedykowany import członków klubu (sekretariat).
 *
 * Route prefix: /club/members/import
 *
 * Workflow:
 *   GET  /club/members/import                 — formularz upload
 *   POST /club/members/import/preview         — parse + walidacja + preview 50 wierszy
 *   POST /club/members/import/execute         — wykonanie importu w transakcji + audit
 *   GET  /club/members/import/template.csv    — pobranie wzorca CSV
 *   GET  /club/members/import/template.xlsx   — wzorzec XLSX (CSV-fallback, gdy brak phpspreadsheet)
 *
 * Bezpieczeństwo:
 *   - requireRole(['zarzad','ksiegowy','admin'])
 *   - CSRF na każdym POST
 *   - max upload 5 MB
 *   - MIME whitelist (text/csv, application/vnd.ms-excel,
 *     application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)
 *   - audit log każdego importu (TenantAccessLogModel::logBypass)
 */
class MemberImportController extends BaseController
{
    private const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // 5 MB
    private const MIME_WHITELIST   = [
        'text/csv',
        'text/plain',
        'application/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    private const EXT_WHITELIST    = ['csv', 'txt', 'xlsx'];

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /club/members/import  — formularz
    // ─────────────────────────────────────────────────────────────────
    public function index(): void
    {
        $this->render('club/members/import/index', [
            'title'           => 'Import członków',
            'dbColumns'       => CsvImporter::dbColumns(),
            'maxUploadBytes'  => self::MAX_UPLOAD_BYTES,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /club/members/import/preview  — preview + auto-map
    // ─────────────────────────────────────────────────────────────────
    public function preview(): void
    {
        Csrf::verify();

        $file = $_FILES['import_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || (int)$file['size'] === 0) {
            Session::flash('error', 'Nie przesłano pliku lub wystąpił błąd przesyłania.');
            $this->redirect('club/members/import');
        }
        if ((int)$file['size'] > self::MAX_UPLOAD_BYTES) {
            Session::flash('error', 'Plik za duży. Maksymalny rozmiar to 5 MB.');
            $this->redirect('club/members/import');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::EXT_WHITELIST, true)) {
            Session::flash('error', 'Dozwolone formaty: ' . implode(', ', self::EXT_WHITELIST));
            $this->redirect('club/members/import');
        }

        // MIME validation (best-effort; finfo dostępne w PHP core)
        $mime = $this->detectMime((string)$file['tmp_name']);
        if ($mime !== null && !in_array($mime, self::MIME_WHITELIST, true)) {
            Session::flash('error', 'Nieprawidłowy typ MIME pliku: ' . $mime);
            $this->redirect('club/members/import');
        }

        // XLSX — wymaga PhpSpreadsheet; jeśli brak, odrzuć z czytelnym komunikatem
        if ($ext === 'xlsx') {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                Session::flash('error',
                    'Format XLSX nie jest obecnie dostępny — proszę zapisać plik jako CSV (UTF-8, średnik). '
                  . 'Wzorzec CSV pobierzesz przyciskiem "Pobierz wzorzec CSV".'
                );
                $this->redirect('club/members/import');
            }
        }

        $uploadDir = ROOT_PATH . '/storage/uploads/import';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $storedName = 'memberimport_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $storedPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file((string)$file['tmp_name'], $storedPath)) {
            Session::flash('error', 'Nie udało się zapisać pliku.');
            $this->redirect('club/members/import');
        }

        // Parse (CSV ścieżka). XLSX byłby tu konwertowany do CSV via PhpSpreadsheet
        // jeżeli dostępny — pominięte celowo (graceful degradation powyżej).
        $delimiter = CsvImporter::detectDelimiter($storedPath);
        $parsed    = CsvImporter::parse($storedPath, $delimiter);

        if (empty($parsed['headers']) || empty($parsed['rows'])) {
            @unlink($storedPath);
            Session::flash('error', 'Plik jest pusty lub nie zawiera prawidłowych danych.');
            $this->redirect('club/members/import');
        }

        $suggestedMapping = CsvImporter::mapColumns($parsed['headers']);

        // Preview pierwszych 50 wierszy
        $previewRows = array_slice($parsed['rows'], 0, 50);

        Session::set('member_import_path', $storedPath);
        Session::set('member_import_delim', $delimiter);
        Session::set('member_import_filename', (string)$file['name']);

        $this->render('club/members/import/preview', [
            'title'            => 'Import członków — podgląd',
            'headers'          => $parsed['headers'],
            'previewRows'      => $previewRows,
            'totalRows'        => count($parsed['rows']),
            'suggestedMapping' => $suggestedMapping,
            'dbColumns'        => CsvImporter::dbColumns(),
            'originalFilename' => (string)$file['name'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /club/members/import/execute  — wykonanie + audit log
    // ─────────────────────────────────────────────────────────────────
    public function execute(): void
    {
        Csrf::verify();

        $storedPath = (string)(Session::get('member_import_path') ?? '');
        $delimiter  = (string)(Session::get('member_import_delim') ?? ';');
        $origName   = (string)(Session::get('member_import_filename') ?? 'import.csv');

        if ($storedPath === '' || !file_exists($storedPath)) {
            Session::flash('error', 'Plik importu nie został znaleziony. Prześlij plik ponownie.');
            $this->redirect('club/members/import');
        }

        $mappingRaw = $_POST['mapping'] ?? [];
        $mapping    = [];
        if (is_array($mappingRaw)) {
            foreach ($mappingRaw as $csvIndex => $dbColumn) {
                $col = is_string($dbColumn) ? trim($dbColumn) : '';
                if ($col !== '' && $col !== '--') {
                    $mapping[(int)$csvIndex] = $col;
                }
            }
        }
        if (empty($mapping)) {
            Session::flash('error', 'Nie przypisano żadnej kolumny. Ustaw mapowanie i spróbuj ponownie.');
            $this->redirect('club/members/import');
        }

        $parsed = CsvImporter::parse($storedPath, $delimiter);
        $clubId = $this->currentClub();
        $userId = (int)(Auth::id() ?? 0);

        $result = CsvImporter::import($clubId, $parsed['rows'], $mapping, $userId);

        // Audit log: kto / kiedy / ile rows / plik
        try {
            (new TenantAccessLogModel())->logBypass(
                'members',
                'csv_import',
                __FILE__,
                __LINE__,
                self::class,
                'info',
                sprintf(
                    'club_id=%d;file=%s;rows=%d;imported=%d;skipped=%d',
                    $clubId,
                    substr(basename($origName), 0, 80),
                    count($parsed['rows']),
                    (int)($result['imported'] ?? 0),
                    (int)($result['skipped']  ?? 0)
                )
            );
        } catch (\Throwable) {}

        @unlink($storedPath);
        Session::remove('member_import_path');
        Session::remove('member_import_delim');
        Session::remove('member_import_filename');

        $this->render('club/members/import/result', [
            'title'    => 'Import członków — wynik',
            'imported' => (int)($result['imported'] ?? 0),
            'skipped'  => (int)($result['skipped']  ?? 0),
            'errors'   => (array)($result['errors']  ?? []),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /club/members/import/template.csv
    // ─────────────────────────────────────────────────────────────────
    public function templateCsv(): void
    {
        $filename = 'wzorzec_import_czlonkow.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM (Excel kompatybilność)
        fwrite($out, "\xEF\xBB\xBF");

        $headers = [
            'imie', 'nazwisko', 'email', 'telefon',
            'data_urodzenia', 'pesel', 'plec',
            'ulica', 'miasto', 'kod_pocztowy',
            'numer_czlonkowski', 'data_dolaczenia', 'status', 'uwagi',
        ];
        fputcsv($out, $headers, ';');

        // Wiersz przykładowy
        fputcsv($out, [
            'Jan', 'Kowalski', 'jan.kowalski@example.com', '+48123456789',
            '1990-05-12', '90051212345', 'M',
            'ul. Sportowa 5', 'Warszawa', '00-001',
            '', date('Y-m-d'), 'aktywny', 'przykładowy wiersz — usuń przed importem',
        ], ';');

        fclose($out);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /club/members/import/template.xlsx
    // (Graceful fallback: jeśli brak PhpSpreadsheet — serwuj CSV z mime XLSX
    //  i wyraźnym komunikatem w pliku, by użytkownik zapisał ponownie.)
    // ─────────────────────────────────────────────────────────────────
    public function templateXlsx(): void
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            // Brak biblioteki → zwróć CSV jako fallback z komunikatem
            Session::flash('info',
                'Wzorzec XLSX wymaga biblioteki PhpSpreadsheet (niedostępna). '
              . 'Pobierz wzorzec CSV — można otworzyć w Excel/LibreOffice.'
            );
            $this->redirect('club/members/import');
        }
        // Jeżeli kiedyś biblioteka zostanie dodana — tu generowalibyśmy XLSX.
        $this->redirect('club/members/import');
    }

    // ─────────────────────────────────────────────────────────────────

    private function detectMime(string $path): ?string
    {
        if (!function_exists('finfo_open')) {
            return null;
        }
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f === false) {
            return null;
        }
        $mime = finfo_file($f, $path);
        finfo_close($f);
        return $mime !== false ? (string)$mime : null;
    }
}
