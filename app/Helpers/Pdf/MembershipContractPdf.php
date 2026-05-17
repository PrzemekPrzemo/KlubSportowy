<?php

declare(strict_types=1);

namespace App\Helpers\Pdf;

use App\Helpers\PdfHelper;
use App\Helpers\Translator;

/**
 * Umowa członkowska między klubem a członkiem (lub jego opiekunem, jeśli niepełnoletni).
 * A4 portret.
 *
 * Dane wejściowe:
 *   - club:    array (name, address, city, nip, regon, krs)
 *   - member:  array (first_name, last_name, pesel, birth_date, address_street, address_city,
 *                     address_postal, email, phone, member_number, join_date, preferred_locale)
 *   - sport_label:  string
 *   - fee:     array (amount, frequency 'miesiac'|'kwartal'|'rok', method 'przelew'|'gotowka')
 *   - duration:     string (np. "czas nieokreślony")
 *   - guardian:     array|null (first_name, last_name, pesel) — jeśli niepełnoletni
 *   - custom_terms: string|null — opcjonalny dodatkowy tekst postanowień
 *   - signed_at:    string (data)
 *   - signed_place: string (miejscowość)
 *   - club_header_html: string
 */
class MembershipContractPdf
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

        $clubHeader = $data['club_header_html'] ?? '';
        $club   = $data['club']   ?? [];
        $member = $data['member'] ?? [];
        $fee    = $data['fee']    ?? [];
        $guard  = $data['guardian'] ?? null;

        $isMinor = self::isMinor((string)($member['birth_date'] ?? ''));

        $clubName    = (string)($club['name'] ?? __('pdf.contract.club'));
        $clubAddr    = trim(($club['address'] ?? '') . ', ' . ($club['city'] ?? ''), ' ,');
        $clubNip     = (string)($club['nip']   ?? '—');
        $clubRegon   = (string)($club['regon'] ?? '—');
        $clubKrs     = (string)($club['krs']   ?? '');

        $memberFull  = $e(trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')));
        $memberPesel = $e($member['pesel']      ?? '—');
        $memberBirth = $e($member['birth_date'] ?? '—');
        $memberAddr  = $e(trim(
            ($member['address_street'] ?? '') . ', ' .
            ($member['address_postal'] ?? '') . ' ' .
            ($member['address_city']   ?? ''),
            ' ,'
        ));
        $memberMail  = $e($member['email'] ?? '');
        $memberPhone = $e($member['phone'] ?? '');
        $memberNo    = $e($member['member_number'] ?? '');

        $sportLabel  = (string)($data['sport_label'] ?? '—');
        $duration    = (string)($data['duration']    ?? __('pdf.contract.duration_indefinite'));

        $feeAmount   = isset($fee['amount']) ? number_format((float)$fee['amount'], 2, ',', ' ') . ' ' . __('pdf.common.currency_pln') : '—';
        $feeFreq     = (string)($fee['frequency'] ?? __('pdf.contract.fee_freq_default'));
        $feeMethod   = (string)($fee['method']    ?? __('pdf.contract.fee_method_default'));

        $signedAt    = (string)($data['signed_at']    ?? date('d.m.Y'));
        $signedPlace = (string)($data['signed_place'] ?? ($club['city'] ?? ''));

        $clubKrsLine = $clubKrs !== '' ? ', KRS: ' . $clubKrs : '';

        // i18n labels
        $title           = $e(__('pdf.contract.title'));
        $signedAtPhrase  = $e(__('pdf.contract.signed_at', ['date' => $signedAt, 'place' => $signedPlace]));
        $partiesTitle    = $e(__('pdf.contract.parties'));
        $clubClause      = __('pdf.contract.club_clause', [
            'club'  => $clubName,
            'addr'  => $clubAddr,
            'nip'   => $clubNip,
            'regon' => $clubRegon,
            'krs'   => $clubKrsLine,
        ]);
        // Allow <strong> in clause: build manually so club name is bolded.
        $clubClauseHtml = '<strong>' . $e($clubName) . '</strong>, ' . $e($clubAddr)
            . ', ' . $e(__('pdf.invoice.label.nip')) . ': ' . $e($clubNip)
            . ', ' . $e(__('pdf.invoice.label.regon')) . ': ' . $e($clubRegon) . $e($clubKrsLine);

        $memberLbl       = $e(__('pdf.contract.member_label'));
        $memberPeselLbl  = $e(__('pdf.contract.member_pesel'));
        $memberBirthLbl  = $e(__('pdf.contract.member_birth'));
        $memberAddrLbl   = $e(__('pdf.contract.member_address'));
        $memberContactLbl = $e(__('pdf.contract.member_contact'));
        $memberNoLbl     = $e(__('pdf.contract.member_no'));
        $s1Title         = $e(__('pdf.contract.s1_title'));
        $s1Body          = $e(__('pdf.contract.s1_body', ['sport' => $sportLabel]));
        $s2Title         = $e(__('pdf.contract.s2_title'));
        $s2Body          = $e(__('pdf.contract.s2_body', ['amount' => $feeAmount, 'freq' => $feeFreq, 'method' => $feeMethod]));
        $s3Title         = $e(__('pdf.contract.s3_title'));
        $s3Body          = $e(__('pdf.contract.s3_body', ['duration' => $duration]));
        $s4Title         = $e(__('pdf.contract.s4_title'));
        $s4Body1         = $e(__('pdf.contract.s4_body_1'));
        $s4Body2         = $e(__('pdf.contract.s4_body_2'));
        $s4Body3         = $e(__('pdf.contract.s4_body_3'));
        $s4Body4         = $e(__('pdf.contract.s4_body_4'));
        $customTermsLbl  = $e(__('pdf.contract.custom_terms_title'));
        $sigClub         = $e(__('pdf.contract.sig_club'));
        $sigLeft         = $isMinor ? $e(__('pdf.contract.sig_guardian')) : $e(__('pdf.contract.sig_member'));
        $generatedLbl    = $e(__('pdf.contract.generated_at'));
        $htmlLang        = $e(Translator::getLocale());

        $guardianHtml = '';
        if ($isMinor && is_array($guard)) {
            $gname  = trim(($guard['first_name'] ?? '') . ' ' . ($guard['last_name'] ?? ''));
            $gpesel = (string)($guard['pesel'] ?? '—');
            $guardianHtml = '<p>' . $e(__('pdf.contract.guardian_clause', ['name' => $gname, 'pesel' => $gpesel])) . '</p>';
        }

        $customTerms = '';
        if (!empty($data['custom_terms'])) {
            $custom = nl2br($e($data['custom_terms']));
            $customTerms = '<div class="section"><div class="section-title">' . $customTermsLbl . '</div><p class="paragraph">' . $custom . '</p></div>';
        }

        $genTime  = $e(date('d.m.Y H:i'));

        return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLang}"><head><meta charset="UTF-8"><style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
  h1 { text-align:center; font-size: 18px; margin: 16px 0 4px 0; }
  .subtitle { text-align:center; font-size: 10px; color: #777; margin-bottom: 22px; }
  .section { margin-bottom: 14px; }
  .section-title { font-size: 12px; font-weight: bold; border-bottom: 1px solid #999; padding-bottom: 3px; margin-bottom: 6px; color: #0d6efd; }
  .paragraph { text-align: justify; line-height: 1.55; margin: 6px 0; font-size: 10.5px; }
  table.data { width: 100%; border-collapse: collapse; }
  table.data td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; vertical-align: top; }
  table.data td.l { font-weight: bold; background: #f5f5f5; width: 30%; }
  .sig-area { margin-top: 50px; }
  .sig-line { border-top: 1px solid #333; width: 220px; display: inline-block; margin-top: 50px; padding-top: 4px; text-align: center; font-size: 9px; color: #666; }
  .footer { margin-top: 30px; font-size: 8px; color: #aaa; text-align: right; }
</style></head><body>
{$clubHeader}

<h1>{$title}</h1>
<div class="subtitle">{$signedAtPhrase}</div>

<div class="section">
  <div class="section-title">{$partiesTitle}</div>
  <p class="paragraph">
    {$clubClauseHtml}
  </p>
  <table class="data">
    <tr><td class="l">{$memberLbl}</td><td>{$memberFull}</td></tr>
    <tr><td class="l">{$memberPeselLbl}</td><td>{$memberPesel}</td></tr>
    <tr><td class="l">{$memberBirthLbl}</td><td>{$memberBirth}</td></tr>
    <tr><td class="l">{$memberAddrLbl}</td><td>{$memberAddr}</td></tr>
    <tr><td class="l">{$memberContactLbl}</td><td>{$memberMail} · {$memberPhone}</td></tr>
    <tr><td class="l">{$memberNoLbl}</td><td>{$memberNo}</td></tr>
  </table>
  {$guardianHtml}
</div>

<div class="section">
  <div class="section-title">{$s1Title}</div>
  <p class="paragraph">{$s1Body}</p>
</div>

<div class="section">
  <div class="section-title">{$s2Title}</div>
  <p class="paragraph">{$s2Body}</p>
</div>

<div class="section">
  <div class="section-title">{$s3Title}</div>
  <p class="paragraph">{$s3Body}</p>
</div>

<div class="section">
  <div class="section-title">{$s4Title}</div>
  <p class="paragraph">{$s4Body1}</p>
  <p class="paragraph">{$s4Body2}</p>
  <p class="paragraph">{$s4Body3}</p>
  <p class="paragraph">{$s4Body4}</p>
</div>

{$customTerms}

<div class="sig-area">
  <table width="100%"><tr>
    <td style="text-align:center;"><div class="sig-line">{$sigLeft}</div></td>
    <td style="text-align:center;"><div class="sig-line">{$sigClub}</div></td>
  </tr></table>
</div>

<div class="footer">{$generatedLbl}: {$genTime}</div>
</body></html>
HTML;
    }

    public static function download(array $data, ?string $filename = null, ?string $locale = null): never
    {
        $html = self::generate($data, $locale);
        $name = $filename ?? ('umowa-czlonkowska-' . preg_replace(
            '/[^a-z0-9_\-]/i', '_', (string)($data['member']['member_number'] ?? ($data['member']['id'] ?? 'czlonek'))
        ) . '.pdf');
        PdfHelper::renderToPdf($html, $name, 'P');
        exit;
    }

    private static function isMinor(string $birthDate): bool
    {
        if ($birthDate === '') return false;
        $ts = strtotime($birthDate);
        if ($ts === false) return false;
        return (time() - $ts) < (18 * 365.25 * 86400);
    }
}
