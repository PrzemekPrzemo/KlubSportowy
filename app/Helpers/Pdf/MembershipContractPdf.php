<?php

declare(strict_types=1);

namespace App\Helpers\Pdf;

use App\Helpers\PdfHelper;

/**
 * Umowa członkowska między klubem a członkiem (lub jego opiekunem, jeśli niepełnoletni).
 * A4 portret.
 *
 * Dane wejściowe:
 *   - club:    array (name, address, city, nip, regon, krs)
 *   - member:  array (first_name, last_name, pesel, birth_date, address_street, address_city,
 *                     address_postal, email, phone, member_number, join_date)
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
    public static function generate(array $data): string
    {
        $e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $clubHeader = $data['club_header_html'] ?? '';
        $club   = $data['club']   ?? [];
        $member = $data['member'] ?? [];
        $fee    = $data['fee']    ?? [];
        $guard  = $data['guardian'] ?? null;

        $isMinor = self::isMinor((string)($member['birth_date'] ?? ''));

        $clubName    = $e($club['name'] ?? 'Klub');
        $clubAddr    = $e(trim(($club['address'] ?? '') . ', ' . ($club['city'] ?? ''), ' ,'));
        $clubNip     = $e($club['nip']   ?? '—');
        $clubRegon   = $e($club['regon'] ?? '—');
        $clubKrs     = $e($club['krs']   ?? '');

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

        $sportLabel  = $e($data['sport_label'] ?? '—');
        $duration    = $e($data['duration']    ?? 'czas nieokreślony');

        $feeAmount   = isset($fee['amount']) ? number_format((float)$fee['amount'], 2, ',', ' ') . ' PLN' : '—';
        $feeFreq     = $e($fee['frequency'] ?? 'miesięcznie');
        $feeMethod   = $e($fee['method']    ?? 'przelew bankowy');

        $signedAt    = $e($data['signed_at']    ?? date('d.m.Y'));
        $signedPlace = $e($data['signed_place'] ?? ($club['city'] ?? ''));

        $guardianHtml = '';
        if ($isMinor && is_array($guard)) {
            $gname  = $e(trim(($guard['first_name'] ?? '') . ' ' . ($guard['last_name'] ?? '')));
            $gpesel = $e($guard['pesel'] ?? '—');
            $guardianHtml = <<<HTML
<p>
  Z uwagi na fakt, że Członek jest osobą niepełnoletnią, umowę w jego imieniu zawiera
  opiekun prawny: <strong>{$gname}</strong> (PESEL {$gpesel}).
</p>
HTML;
        }

        $customTerms = '';
        if (!empty($data['custom_terms'])) {
            $custom = nl2br($e($data['custom_terms']));
            $customTerms = '<div class="section"><div class="section-title">Postanowienia dodatkowe</div><p class="paragraph">' . $custom . '</p></div>';
        }

        $sigLeft  = $isMinor ? 'Podpis opiekuna prawnego' : 'Podpis Członka';
        $genTime  = $e(date('d.m.Y H:i'));
        $clubKrsLine = $clubKrs !== '' ? ', KRS: ' . $clubKrs : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="pl"><head><meta charset="UTF-8"><style>
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

<h1>UMOWA CZŁONKOWSKA</h1>
<div class="subtitle">zawarta w dniu {$signedAt} w {$signedPlace}</div>

<div class="section">
  <div class="section-title">Strony umowy</div>
  <p class="paragraph">
    <strong>Klub:</strong> {$clubName}, {$clubAddr}, NIP: {$clubNip}, REGON: {$clubRegon}{$clubKrsLine} —
    zwany dalej „Klubem", reprezentowany przez Zarząd.
  </p>
  <table class="data">
    <tr><td class="l">Członek (imię i nazwisko)</td><td>{$memberFull}</td></tr>
    <tr><td class="l">PESEL</td><td>{$memberPesel}</td></tr>
    <tr><td class="l">Data urodzenia</td><td>{$memberBirth}</td></tr>
    <tr><td class="l">Adres zamieszkania</td><td>{$memberAddr}</td></tr>
    <tr><td class="l">Kontakt</td><td>{$memberMail} · {$memberPhone}</td></tr>
    <tr><td class="l">Nr ewidencyjny</td><td>{$memberNo}</td></tr>
  </table>
  {$guardianHtml}
</div>

<div class="section">
  <div class="section-title">§1. Przedmiot umowy</div>
  <p class="paragraph">
    Przedmiotem umowy jest członkostwo w sekcji sportowej <strong>{$sportLabel}</strong>
    prowadzonej przez Klub oraz uczestnictwo w treningach, zawodach i innych
    wydarzeniach organizowanych przez Klub w ramach działalności statutowej.
  </p>
</div>

<div class="section">
  <div class="section-title">§2. Składki członkowskie</div>
  <p class="paragraph">
    Członek zobowiązuje się do opłacania składek członkowskich w wysokości
    <strong>{$feeAmount}</strong>, płatnych <strong>{$feeFreq}</strong> w formie:
    <strong>{$feeMethod}</strong>. Składki płatne są z góry, do 10. dnia każdego okresu rozliczeniowego.
  </p>
</div>

<div class="section">
  <div class="section-title">§3. Czas trwania umowy</div>
  <p class="paragraph">
    Umowa zostaje zawarta na <strong>{$duration}</strong>. Każdej ze stron przysługuje prawo
    rozwiązania umowy z zachowaniem jednomiesięcznego okresu wypowiedzenia ze
    skutkiem na koniec miesiąca kalendarzowego, składanego w formie pisemnej.
  </p>
</div>

<div class="section">
  <div class="section-title">§4. Postanowienia ogólne</div>
  <p class="paragraph">
    1. Członek oświadcza, że zapoznał się ze Statutem oraz Regulaminem wewnętrznym Klubu
    i zobowiązuje się do ich przestrzegania.
  </p>
  <p class="paragraph">
    2. Członek wyraża zgodę na przetwarzanie swoich danych osobowych w zakresie niezbędnym
    do realizacji celów statutowych Klubu, zgodnie z RODO (Rozporządzenie 2016/679).
  </p>
  <p class="paragraph">
    3. W sprawach nieuregulowanych umową zastosowanie mają przepisy Kodeksu cywilnego
    oraz Statut Klubu.
  </p>
  <p class="paragraph">
    4. Umowę sporządzono w dwóch jednobrzmiących egzemplarzach — po jednym dla każdej ze stron.
  </p>
</div>

{$customTerms}

<div class="sig-area">
  <table width="100%"><tr>
    <td style="text-align:center;"><div class="sig-line">{$sigLeft}</div></td>
    <td style="text-align:center;"><div class="sig-line">Podpis przedstawiciela Klubu</div></td>
  </tr></table>
</div>

<div class="footer">Wygenerowano: {$genTime}</div>
</body></html>
HTML;
    }

    public static function download(array $data, ?string $filename = null): never
    {
        $html = self::generate($data);
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
