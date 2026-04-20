<?php

declare(strict_types=1);

namespace App\Helpers;

class BeltCertificatePdf
{
    /**
     * Generate a belt certificate PDF and send it inline to the browser.
     *
     * @param array  $belt      Belt record (belt_level, granted_date, examiner, location)
     * @param array  $member    Member record (first_name, last_name, member_number, birth_date)
     * @param array  $beltMap   Static belt map from the sport model (key => ['label', 'color', ...])
     * @param string $sportName Display name of the sport (e.g. "Judo")
     * @param string $federation Federation abbreviation (e.g. "PZJ")
     */
    public static function generate(
        array $belt,
        array $member,
        array $beltMap,
        string $sportName,
        string $federation
    ): void {
        $beltInfo   = $beltMap[$belt['belt_level']] ?? ['label' => $belt['belt_level'], 'color' => '#cccccc'];
        $beltLabel  = htmlspecialchars($beltInfo['label'], ENT_QUOTES, 'UTF-8');
        $beltColor  = htmlspecialchars($beltInfo['color'], ENT_QUOTES, 'UTF-8');

        $fullName   = htmlspecialchars(
            trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')),
            ENT_QUOTES,
            'UTF-8'
        );
        $memberNo   = htmlspecialchars($member['member_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $sportHtml  = htmlspecialchars($sportName, ENT_QUOTES, 'UTF-8');
        $fedHtml    = htmlspecialchars($federation, ENT_QUOTES, 'UTF-8');

        $grantedDate = self::formatPolishDate($belt['granted_date'] ?? '');
        $examiner    = trim($belt['examiner'] ?? '');
        $location    = trim($belt['location'] ?? '');

        // Determine text colour for the belt colour bar
        $darkColors  = ['#000', '#000000', '#8B4513', '#6b3410', '#4a2409', '#cc0000', '#dc3545', '#c0392b'];
        $barTextColor = in_array(strtolower($beltColor), array_map('strtolower', $darkColors), true)
            ? '#ffffff'
            : '#333333';

        $examinerHtml = '';
        if ($examiner !== '') {
            $examinerHtml = '<p style="font-size:13px; margin:6px 0 0 0; color:#555;">
                Egzaminator: <strong>' . htmlspecialchars($examiner, ENT_QUOTES, 'UTF-8') . '</strong>
            </p>';
        }

        $locationHtml = '';
        if ($location !== '') {
            $locationHtml = '<p style="font-size:13px; margin:4px 0 0 0; color:#555;">
                Miejsce: <strong>' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '</strong>
            </p>';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        margin: 0;
        padding: 0;
        background: #fff;
    }
    .page {
        width: 100%;
        padding: 30px 40px;
    }
    .header {
        text-align: center;
        border-bottom: 3px solid #333;
        padding-bottom: 14px;
        margin-bottom: 20px;
    }
    .header-title {
        font-size: 22px;
        font-weight: bold;
        letter-spacing: 1px;
        color: #222;
    }
    .header-subtitle {
        font-size: 14px;
        color: #666;
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .belt-bar {
        background: {$beltColor};
        color: {$barTextColor};
        text-align: center;
        padding: 10px 20px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 4px;
        margin-bottom: 24px;
        letter-spacing: 1px;
    }
    .cert-body {
        text-align: center;
        padding: 10px 0 20px 0;
    }
    .cert-intro {
        font-size: 14px;
        color: #555;
        margin-bottom: 10px;
    }
    .cert-name {
        font-size: 36px;
        font-weight: bold;
        color: #111;
        margin: 8px 0 4px 0;
    }
    .cert-member-no {
        font-size: 12px;
        color: #888;
        margin-bottom: 20px;
    }
    .cert-awarded {
        font-size: 14px;
        color: #444;
        margin: 10px 0 4px 0;
    }
    .cert-date {
        font-size: 16px;
        font-weight: bold;
        color: #222;
        margin-bottom: 10px;
    }
    .cert-details {
        margin-top: 10px;
    }
    .footer {
        border-top: 2px solid #333;
        padding-top: 12px;
        margin-top: 30px;
        text-align: center;
    }
    .footer-fed {
        font-size: 13px;
        color: #444;
        font-weight: bold;
        letter-spacing: 1px;
    }
    .footer-generated {
        font-size: 10px;
        color: #aaa;
        margin-top: 4px;
    }
    .divider {
        border: none;
        border-top: 1px solid #ddd;
        margin: 16px auto;
        width: 60%;
    }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-title">{$sportHtml}</div>
        <div class="header-subtitle">Certyfikat Pasa</div>
    </div>

    <div class="belt-bar">{$beltLabel}</div>

    <div class="cert-body">
        <p class="cert-intro">Niniejszym zaświadcza się, że</p>
        <p class="cert-name">{$fullName}</p>
HTML;

        if ($memberNo !== '') {
            $html .= '<p class="cert-member-no">Nr członkowski: ' . $memberNo . '</p>';
        }

        $html .= <<<HTML
        <hr class="divider">
        <p class="cert-awarded">uzyskał(-a) stopień</p>
        <p style="font-size:20px; font-weight:bold; color:#333; margin:6px 0;">{$beltLabel}</p>
        <p class="cert-awarded">w dniu</p>
        <p class="cert-date">{$grantedDate}</p>
        <div class="cert-details">
            {$examinerHtml}
            {$locationHtml}
        </div>
    </div>

    <div class="footer">
        <div class="footer-fed">{$fedHtml} — Certyfikat Pasa {$sportHtml}</div>
        <div class="footer-generated">Wygenerowano: HTML;

        $html .= date('d.m.Y H:i');

        $html .= <<<HTML
</div>
    </div>
</div>
</body>
</html>
HTML;

        $filename = 'certyfikat_pasa_' . preg_replace('/[^a-z0-9_]/i', '_', $fullName) . '.pdf';
        PdfHelper::renderToPdf($html, $filename, 'L');
    }

    /**
     * Format a Y-m-d date string as Polish long-form date (e.g. "1 stycznia 2024").
     */
    private static function formatPolishDate(string $date): string
    {
        if ($date === '') {
            return '';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }
        $months = [
            1  => 'stycznia',
            2  => 'lutego',
            3  => 'marca',
            4  => 'kwietnia',
            5  => 'maja',
            6  => 'czerwca',
            7  => 'lipca',
            8  => 'sierpnia',
            9  => 'września',
            10 => 'października',
            11 => 'listopada',
            12 => 'grudnia',
        ];
        $day   = (int)date('j', $ts);
        $month = (int)date('n', $ts);
        $year  = (int)date('Y', $ts);
        return $day . ' ' . ($months[$month] ?? date('m', $ts)) . ' ' . $year;
    }
}
