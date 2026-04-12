<?php

declare(strict_types=1);

namespace App\Helpers;

class CsvExporter
{
    /**
     * Send a CSV file download to the browser.
     *
     * @param string   $filename  Download filename (e.g. "members.csv")
     * @param string[] $headers   Column headers
     * @param array[]  $rows      Array of rows (each row is an array of values)
     */
    public static function download(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for proper Excel encoding
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }
}
