<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;

class PdfHelper
{
    /**
     * Render HTML string to PDF and send to browser, or fallback to HTML output.
     *
     * @param string $html        Full HTML content
     * @param string $filename    Download filename (e.g. "members.pdf")
     * @param string $orientation 'P' for portrait, 'L' for landscape
     */
    public static function renderToPdf(string $html, string $filename, string $orientation = 'P'): void
    {
        if (class_exists(\Mpdf\Mpdf::class)) {
            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'orientation'   => $orientation,
                'margin_left'   => 15,
                'margin_right'  => 15,
                'margin_top'    => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9,
                'tempDir'       => ROOT_PATH . '/storage/tmp',
            ]);
            $mpdf->SetTitle($filename);
            $mpdf->WriteHTML($html);
            $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
            exit;
        }

        // Fallback: output HTML with print-friendly CSS
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8">';
        echo '<title>' . htmlspecialchars($filename) . '</title>';
        echo '<style>@media print { body { margin: 0; } } body { font-family: sans-serif; margin: 2em; }</style>';
        echo '</head><body>';
        echo $html;
        echo '<script>window.print();</script>';
        echo '</body></html>';
        exit;
    }

    /**
     * Return HTML header block with club logo and name for PDF templates.
     */
    public static function getClubHeader(int $clubId): string
    {
        $club = (new ClubModel())->findById($clubId);
        $cust = (new ClubCustomizationModel())->findForClub($clubId);

        $clubName = htmlspecialchars($club['name'] ?? 'Klub Sportowy', ENT_QUOTES, 'UTF-8');
        $motto    = htmlspecialchars($cust['motto'] ?? '', ENT_QUOTES, 'UTF-8');
        $logoPath = $cust['logo_path'] ?? '';

        $logoHtml = '';
        if ($logoPath !== '' && $logoPath !== null) {
            $fullPath = ROOT_PATH . '/public/' . ltrim($logoPath, '/');
            if (file_exists($fullPath)) {
                $logoHtml = '<img src="' . $fullPath . '" style="max-height:60px; max-width:180px; margin-right:15px;" />';
            }
        }

        $html  = '<div style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px;">';
        $html .= '<table width="100%"><tr>';
        $html .= '<td style="vertical-align:middle;">' . $logoHtml . '</td>';
        $html .= '<td style="vertical-align:middle;">';
        $html .= '<span style="font-size: 18px; font-weight: bold;">' . $clubName . '</span>';
        if ($motto !== '') {
            $html .= '<br><span style="font-size: 11px; color: #666; font-style: italic;">' . $motto . '</span>';
        }
        $html .= '</td>';
        $html .= '<td style="text-align:right; vertical-align:middle; font-size:11px; color:#888;">';
        $html .= 'Wygenerowano: ' . date('d.m.Y H:i');
        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '</div>';

        return $html;
    }
}
