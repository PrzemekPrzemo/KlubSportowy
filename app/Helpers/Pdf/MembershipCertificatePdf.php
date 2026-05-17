<?php

declare(strict_types=1);

namespace App\Helpers\Pdf;

use App\Helpers\PdfHelper;
use App\Helpers\Translator;

/**
 * Zaświadczenie o członkostwie — generator PDF (A4 portret).
 *
 * Wymagane dane wejściowe:
 *   - club:    array  (name, address, nip, city, …)
 *   - member:  array  (first_name, last_name, pesel, member_number, join_date, preferred_locale)
 *   - sport_label:   string  (np. "Judo", "Karate")
 *   - paid_until:    string|null (Y-m-d, opcjonalne)
 *   - issued_at:     string  (data wystawienia, domyślnie dziś)
 *   - issued_place:  string  (miejscowość wystawienia)
 *   - club_header_html: string (gotowy HTML nagłówka z PdfHelper::getClubHeader)
 */
class MembershipCertificatePdf
{
    /**
     * Buduje pełny HTML zaświadczenia (bez wysyłania na wyjście).
     *
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

        $club   = $data['club']   ?? [];
        $member = $data['member'] ?? [];

        $clubHeader = $data['club_header_html'] ?? '';
        $clubName   = $e($club['name']    ?? 'Klub Sportowy');
        $clubAddr   = $e($club['address'] ?? '');
        $clubCity   = $e($club['city']    ?? '');
        $clubNip    = $e($club['nip']     ?? '');

        $fullName   = $e(trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')));
        $pesel      = $e($member['pesel'] ?? '');
        $memberNo   = $e($member['member_number'] ?? '');
        $joinDate   = self::formatLocalDate((string)($member['join_date'] ?? ''), Translator::getLocale());

        $sportLabel = $e($data['sport_label']  ?? '—');
        $paidUntil  = !empty($data['paid_until']) ? self::formatLocalDate((string)$data['paid_until'], Translator::getLocale()) : '—';

        $issuedAt    = $e($data['issued_at']    ?? date('d.m.Y'));
        $issuedPlace = $e($data['issued_place'] ?? ($club['city'] ?? ''));
        $genTime     = $e(date('d.m.Y H:i'));

        $peselLabel = $e(__('pdf.member_cert.pesel'));
        $peselLine = $pesel !== ''
            ? ' (' . $peselLabel . ' <strong>' . $pesel . '</strong>)'
            : '';

        $nipLabel = $e(__('pdf.invoice.label.nip'));

        $clubMeta = [];
        if ($clubAddr !== '') $clubMeta[] = $clubAddr;
        if ($clubCity !== '') $clubMeta[] = $clubCity;
        if ($clubNip  !== '') $clubMeta[] = $nipLabel . ' ' . $clubNip;
        $clubMetaHtml = $clubMeta ? '<p style="font-size:10px;color:#666;margin:0 0 14px 0;">' . implode(' · ', $clubMeta) . '</p>' : '';

        // i18n labels
        $title         = $e(__('pdf.member_cert.title'));
        $subtitle      = $e(__('pdf.member_cert.subtitle'));
        $intro         = $e(__('pdf.member_cert.intro'));
        $isMember      = $e(__('pdf.member_cert.is_member'));
        $sinceDay      = $e(__('pdf.member_cert.since_day'));
        $inSection     = $e(__('pdf.member_cert.in_section'));
        $memberNoLabel = $e(__('pdf.member_cert.member_no'));
        $sectionLabel  = $e(__('pdf.member_cert.section'));
        $memberSince   = $e(__('pdf.member_cert.member_since'));
        $feesPaidUntil = $e(__('pdf.member_cert.fees_paid_until'));
        $issuedNote    = $e(__('pdf.member_cert.issued_note'));
        $placeDate     = $e(__('pdf.member_cert.place_date', ['place' => $data['issued_place'] ?? ($club['city'] ?? ''), 'date' => $data['issued_at'] ?? date('d.m.Y')]));
        $signature     = $e(__('pdf.member_cert.signature'));
        $generatedLbl  = $e(__('pdf.member_cert.generated_at'));
        $htmlLang      = $e(Translator::getLocale());

        return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLang}">
<head><meta charset="UTF-8"><style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
  h1 { text-align:center; font-size:22px; letter-spacing:3px; margin: 30px 0 6px 0; }
  .subtitle { text-align:center; font-size:11px; color:#888; text-transform:uppercase; letter-spacing:2px; margin-bottom: 30px; }
  .body { line-height: 1.8; text-align: justify; margin: 0 20px; }
  .body p { margin: 12px 0; }
  .data { background: #f7f7f9; border-left: 4px solid #0d6efd; padding: 12px 16px; margin: 18px 0; }
  .data div { margin: 4px 0; font-size: 11px; }
  .label { display:inline-block; min-width:160px; color:#555; }
  .place-date { margin-top: 60px; text-align: right; font-size: 11px; }
  .signature { margin-top: 80px; text-align: center; }
  .sig-line { border-top: 1px solid #333; display:inline-block; width: 260px; margin-top: 50px; padding-top:4px; font-size:10px; color:#666; }
  .footer { margin-top: 40px; font-size: 9px; color: #aaa; text-align: center; }
</style></head><body>
{$clubHeader}
{$clubMetaHtml}

<h1>{$title}</h1>
<div class="subtitle">{$subtitle}</div>

<div class="body">
  <p>
    {$intro}
  </p>
  <p style="text-align:center; font-size:18px; font-weight:bold; margin:18px 0;">
    {$fullName}{$peselLine}
  </p>
  <p>
    {$isMember} <strong>{$clubName}</strong> {$sinceDay}
    <strong>{$joinDate}</strong>, {$inSection}: <strong>{$sportLabel}</strong>.
  </p>

  <div class="data">
    <div><span class="label">{$memberNoLabel}:</span> <strong>{$memberNo}</strong></div>
    <div><span class="label">{$sectionLabel}:</span> {$sportLabel}</div>
    <div><span class="label">{$memberSince}:</span> {$joinDate}</div>
    <div><span class="label">{$feesPaidUntil}:</span> {$paidUntil}</div>
  </div>

  <p>
    {$issuedNote}
  </p>
</div>

<div class="place-date">
  {$placeDate}
</div>

<div class="signature">
  <div class="sig-line">{$signature}</div>
</div>

<div class="footer">
  {$generatedLbl}: {$genTime}
</div>
</body></html>
HTML;
    }

    /**
     * Generuje i wysyła PDF do przeglądarki (exit).
     */
    public static function download(array $data, ?string $filename = null, ?string $locale = null): never
    {
        $html = self::generate($data, $locale);
        $name = $filename
            ?? __('pdf.member_cert.filename') . '-' . preg_replace(
                '/[^a-z0-9_\-]/i',
                '_',
                (string)($data['member']['member_number'] ?? ($data['member']['id'] ?? 'member'))
            ) . '.pdf';
        PdfHelper::renderToPdf($html, $name, 'P');
        exit;
    }

    private static function formatLocalDate(string $date, string $locale): string
    {
        if ($date === '') return '—';
        $ts = strtotime($date);
        if ($ts === false) return $date;
        $monthsPl = [
            1=>'stycznia',2=>'lutego',3=>'marca',4=>'kwietnia',5=>'maja',6=>'czerwca',
            7=>'lipca',8=>'sierpnia',9=>'września',10=>'października',11=>'listopada',12=>'grudnia',
        ];
        $monthsEn = [
            1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
            7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',
        ];
        $months = $locale === 'en' ? $monthsEn : $monthsPl;
        return (int)date('j', $ts) . ' ' . ($months[(int)date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
    }
}
