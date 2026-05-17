<?php

declare(strict_types=1);

namespace App\Helpers\Pdf;

use App\Helpers\PdfHelper;
use App\Helpers\Translator;

/**
 * Certyfikat osiągnięcia (np. ukończenie kursu, miejsce w turnieju).
 * Format A4 landscape z ozdobnym tłem.
 *
 * Dane wejściowe:
 *   - member:      array (first_name, last_name, member_number, preferred_locale)
 *   - achievement: string (np. "Ukończenie kursu pierwszej pomocy" / "I miejsce w Turnieju ...")
 *   - issued_at:   string (data wystawienia)
 *   - issued_place:string (miejscowość)
 *   - club_name:   string
 *   - president_name: string|null
 *   - coach_name:    string|null
 *   - club_header_html: string|null (opcjonalnie — krótka stopka klubu)
 *   - accent_color:  string (np. '#0d6efd') — z brandingu klubu
 */
class AchievementCertificatePdf
{
    /**
     * @param string|null $locale 'pl'|'en'; null = current request locale.
     */
    public static function generate(array $data, ?string $locale = null): string
    {
        if ($locale !== null) {
            return Translator::withLocale($locale, fn() => self::doGenerate($data));
        }
        return self::doGenerate($data);
    }

    private static function doGenerate(array $data): string
    {
        $e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $member       = $data['member'] ?? [];
        $fullName     = $e(trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')));
        $memberNo     = $e($member['member_number'] ?? '');
        $achievement  = $e($data['achievement']   ?? __('pdf.achievement.default_text'));
        $issuedAt     = (string)($data['issued_at']     ?? date('d.m.Y'));
        $issuedPlace  = (string)($data['issued_place']  ?? '');
        $clubName     = $e($data['club_name']     ?? 'Klub Sportowy');
        $accent       = $e(self::normaliseColor((string)($data['accent_color'] ?? '#0d6efd')));
        $president    = $e($data['president_name'] ?? __('pdf.achievement.president'));
        $coach        = $e($data['coach_name']     ?? __('pdf.achievement.coach'));

        $memberNoLbl  = $e(__('pdf.achievement.member_no'));
        $memberLine = $memberNo !== ''
            ? '<div style="font-size:12px;color:#888;margin-top:4px;">' . $memberNoLbl . ': ' . $memberNo . '</div>'
            : '';

        $genTime = $e(date('d.m.Y H:i'));

        // i18n labels
        $title         = $e(__('pdf.achievement.title'));
        $subtitle      = $e(__('pdf.achievement.subtitle'));
        $intro         = $e(__('pdf.achievement.intro'));
        $placeDate     = $e(__('pdf.achievement.place_date', ['place' => $issuedPlace, 'date' => $issuedAt]));
        $generatedLbl  = $e(__('pdf.achievement.generated_at'));
        $htmlLang      = $e(Translator::getLocale());

        return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLang}"><head><meta charset="UTF-8"><style>
  @page { margin: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; padding: 0; }
  .frame {
    width: 96%; margin: 1.5% auto; padding: 30px 50px;
    border: 6px double {$accent};
    border-radius: 8px;
    box-sizing: border-box;
    min-height: 540px;
    text-align: center;
    position: relative;
  }
  .corner {
    position: absolute; width: 60px; height: 60px;
    border: 3px solid {$accent}; border-radius: 50%; opacity: 0.15;
  }
  .corner.tl { top: 14px; left: 14px; }
  .corner.tr { top: 14px; right: 14px; }
  .corner.bl { bottom: 14px; left: 14px; }
  .corner.br { bottom: 14px; right: 14px; }
  .club {
    font-size: 12px; color: #666;
    text-transform: uppercase; letter-spacing: 4px; margin-bottom: 14px;
  }
  h1 {
    font-size: 56px; margin: 10px 0 4px 0;
    color: {$accent};
    letter-spacing: 8px;
  }
  .sub {
    font-size: 14px; color: #555; letter-spacing: 2px;
    text-transform: uppercase; margin-bottom: 30px;
  }
  .recipient {
    font-size: 16px; color: #444; margin-top: 16px;
  }
  .name {
    font-size: 42px; font-weight: bold; color: #111;
    margin: 8px 0 4px 0;
    font-style: italic;
  }
  .achievement {
    font-size: 22px; margin: 24px 50px 18px 50px;
    color: #222; line-height: 1.4;
    border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;
    padding: 14px 0;
  }
  .date-place { font-size: 13px; color: #555; margin-top: 14px; }
  .signatures {
    margin-top: 60px; width: 100%; border-collapse: collapse;
  }
  .signatures td {
    width: 50%; text-align: center; vertical-align: top;
    font-size: 12px; color: #555;
  }
  .sig-line {
    border-top: 1px solid #555;
    display: inline-block; width: 220px; padding-top: 4px;
  }
  .footer { font-size: 8px; color: #bbb; margin-top: 20px; }
</style></head><body>
<div class="frame">
  <span class="corner tl"></span><span class="corner tr"></span>
  <span class="corner bl"></span><span class="corner br"></span>

  <div class="club">{$clubName}</div>
  <h1>{$title}</h1>
  <div class="sub">{$subtitle}</div>

  <div class="recipient">{$intro}</div>
  <div class="name">{$fullName}</div>
  {$memberLine}

  <div class="achievement">{$achievement}</div>

  <div class="date-place">{$placeDate}</div>

  <table class="signatures"><tr>
    <td><div class="sig-line">{$president}</div></td>
    <td><div class="sig-line">{$coach}</div></td>
  </tr></table>

  <div class="footer">{$generatedLbl}: {$genTime}</div>
</div>
</body></html>
HTML;
    }

    public static function download(array $data, ?string $filename = null, ?string $locale = null): never
    {
        $html = self::generate($data, $locale);
        $name = $filename ?? ('certyfikat-' . preg_replace(
            '/[^a-z0-9_\-]/i', '_', (string)($data['member']['member_number'] ?? ($data['member']['id'] ?? 'czlonek'))
        ) . '.pdf');
        PdfHelper::renderToPdf($html, $name, 'L');
        exit;
    }

    private static function normaliseColor(string $hex): string
    {
        $hex = trim($hex);
        if (!preg_match('/^#[0-9a-f]{3,8}$/i', $hex)) {
            return '#0d6efd';
        }
        return $hex;
    }
}
