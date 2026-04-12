<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\CsvImporter;
use App\Helpers\Session;

class ImportController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /**
     * Upload form — GET /import
     */
    public function index(): void
    {
        $this->render('import/index', [
            'title' => 'Import CSV',
        ]);
    }

    /**
     * Receive CSV, parse, show preview + mapping — POST /import/upload
     */
    public function upload(): void
    {
        Csrf::verify();

        $file = $_FILES['csv_file'] ?? null;

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) {
            Session::flash('error', 'Nie przesłano pliku lub wystąpił błąd przesyłania.');
            $this->redirect('import');
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            Session::flash('error', 'Dozwolone formaty: .csv, .txt');
            $this->redirect('import');
        }

        // Ensure upload directory exists
        $uploadDir = ROOT_PATH . '/storage/uploads/import';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        // Store with unique name
        $storedName = 'import_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $storedPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            Session::flash('error', 'Nie udało się zapisać pliku.');
            $this->redirect('import');
        }

        // Parse CSV
        $delimiter = CsvImporter::detectDelimiter($storedPath);
        $parsed    = CsvImporter::parse($storedPath, $delimiter);

        if (empty($parsed['headers']) || empty($parsed['rows'])) {
            @unlink($storedPath);
            Session::flash('error', 'Plik jest pusty lub nie zawiera prawidłowych danych.');
            $this->redirect('import');
        }

        // Auto-suggest column mapping
        $suggestedMapping = CsvImporter::mapColumns($parsed['headers']);

        // Preview first 10 rows
        $previewRows = array_slice($parsed['rows'], 0, 10);

        // Store file path in session for execute step
        Session::set('import_csv_path', $storedPath);
        Session::set('import_csv_delimiter', $delimiter);

        $this->render('import/preview', [
            'title'            => 'Import CSV — podgląd',
            'headers'          => $parsed['headers'],
            'previewRows'      => $previewRows,
            'totalRows'        => count($parsed['rows']),
            'suggestedMapping' => $suggestedMapping,
            'dbColumns'        => CsvImporter::dbColumns(),
        ]);
    }

    /**
     * Execute the import — POST /import/execute
     */
    public function execute(): void
    {
        Csrf::verify();

        $storedPath = Session::get('import_csv_path');
        $delimiter  = Session::get('import_csv_delimiter') ?? ';';

        if (!$storedPath || !file_exists($storedPath)) {
            Session::flash('error', 'Plik importu nie został znaleziony. Prześlij plik ponownie.');
            $this->redirect('import');
        }

        // Build mapping: CSV column index => DB column
        $mappingRaw = $_POST['mapping'] ?? [];
        $mapping    = [];
        foreach ($mappingRaw as $csvIndex => $dbColumn) {
            $dbColumn = trim($dbColumn);
            if ($dbColumn !== '' && $dbColumn !== '--') {
                $mapping[(int)$csvIndex] = $dbColumn;
            }
        }

        if (empty($mapping)) {
            Session::flash('error', 'Nie przypisano żadnej kolumny. Ustaw mapowanie i spróbuj ponownie.');
            $this->redirect('import');
        }

        // Re-parse file
        $parsed = CsvImporter::parse($storedPath, $delimiter);

        // Run import
        $result = CsvImporter::import(
            $this->currentClub(),
            $parsed['rows'],
            $mapping,
            (int)Auth::id()
        );

        // Clean up temp file
        @unlink($storedPath);
        Session::remove('import_csv_path');
        Session::remove('import_csv_delimiter');

        $this->render('import/result', [
            'title'    => 'Import CSV — wynik',
            'imported' => $result['imported'],
            'skipped'  => $result['skipped'],
            'errors'   => $result['errors'],
        ]);
    }
}
