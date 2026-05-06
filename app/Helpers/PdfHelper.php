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
            $tempDir = ROOT_PATH . '/storage/tmp';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }
            if (!is_writable($tempDir)) {
                $tempDir = sys_get_temp_dir() . '/mpdf_' . md5(ROOT_PATH);
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0775, true);
                }
            }

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
                'tempDir'       => $tempDir,
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
     *
     * Y.1 — używa 3-warstwowego brandingu:
     *   1. system_logo (Master Admin)
     *   2. club_logo (klub)
     *   3. sport_logo (opcjonalnie, jeśli przekazano $clubSportId)
     *
     * @param int      $clubId      ID klubu
     * @param int|null $clubSportId opcjonalnie: ID sekcji sportowej (do logo na sekcji)
     */
    public static function getClubHeader(int $clubId, ?int $clubSportId = null): string
    {
        $club = (new ClubModel())->findById($clubId);
        $cust = (new ClubCustomizationModel())->findForClub($clubId);

        $clubName = htmlspecialchars($club['name'] ?? 'Klub Sportowy', ENT_QUOTES, 'UTF-8');
        $motto    = htmlspecialchars($cust['motto'] ?? '', ENT_QUOTES, 'UTF-8');

        // 1. SYSTEM logo (Master Admin) — lewy róg
        $systemLogoHtml = self::renderLogo(
            (new \App\Models\SettingModel())->get('system_logo_color', '') ?: null,
            'max-height:50px; max-width:150px;'
        );

        // 2. CLUB logo — środek (główne)
        $clubLogoHtml = self::renderLogo(
            $cust['logo_path'] ?? null,
            'max-height:60px; max-width:180px; margin-right:15px;'
        );

        // 3. SPORT logo — opcjonalne, prawa strona obok daty
        $sportLogoHtml = '';
        if ($clubSportId !== null) {
            try {
                $cs = (new \App\Models\ClubSportModel())->findById($clubSportId);
                if ($cs && (int)$cs['club_id'] === $clubId) {
                    $sportLogoPath = $cs['logo_main_path'] ?? null;
                    $sportLogoHtml = self::renderLogo(
                        $sportLogoPath,
                        'max-height:40px; max-width:120px; margin-left:10px;'
                    );
                }
            } catch (\Throwable) {}
        }

        $html  = '<div style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px;">';
        $html .= '<table width="100%"><tr>';
        // Lewa: system + klub logo
        $html .= '<td style="vertical-align:middle; width:55%;">';
        $html .= $clubLogoHtml ?: $systemLogoHtml;
        $html .= '</td>';
        // Środek: nazwa klubu
        $html .= '<td style="vertical-align:middle;">';
        $html .= '<span style="font-size: 18px; font-weight: bold;">' . $clubName . '</span>';
        if ($motto !== '') {
            $html .= '<br><span style="font-size: 11px; color: #666; font-style: italic;">' . $motto . '</span>';
        }
        $html .= '</td>';
        // Prawa: sport logo (mały) + data
        $html .= '<td style="text-align:right; vertical-align:middle; font-size:11px; color:#888;">';
        if ($sportLogoHtml) {
            $html .= '<div>' . $sportLogoHtml . '</div>';
        }
        $html .= 'Wygenerowano: ' . date('d.m.Y H:i');
        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Footer dla PDF — system logo (małe) + tekst "Made with ClubDesk".
     * Wywoływane na końcu każdego raportu.
     */
    public static function getSystemFooter(): string
    {
        $systemLogo = (new \App\Models\SettingModel())->get('system_logo_color', '') ?: null;
        $logoHtml   = self::renderLogo($systemLogo, 'max-height:20px; max-width:60px; vertical-align:middle;');

        $html  = '<div style="border-top: 1px solid #ddd; margin-top: 30px; padding-top: 8px; text-align:center; font-size:9px; color:#999;">';
        if ($logoHtml) $html .= $logoHtml . ' ';
        $html .= 'System zarządzania klubem sportowym ClubDesk · ' . htmlspecialchars(BASE_URL ?? 'clubdesk.pl', ENT_QUOTES);
        $html .= '</div>';

        return $html;
    }

    /**
     * Bezpieczny render logo: sprawdza istnienie pliku, escapuje atrybuty,
     * zwraca pusty string gdy brak.
     */
    private static function renderLogo(?string $path, string $style): string
    {
        if (!$path) return '';
        $fullPath = ROOT_PATH . '/public/' . ltrim($path, '/');
        if (!file_exists($fullPath)) return '';
        return '<img src="' . htmlspecialchars($fullPath, ENT_QUOTES) . '" style="' . htmlspecialchars($style, ENT_QUOTES) . '" />';
    }
}
