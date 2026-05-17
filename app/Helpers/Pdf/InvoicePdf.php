<?php

declare(strict_types=1);

namespace App\Helpers\Pdf;

use App\Helpers\PdfHelper;
use App\Helpers\Translator;

/**
 * Faktura / rachunek — generator PDF (A4 portret).
 *
 * Wymagane dane wejściowe:
 *   - seller:    array (name, address, city, nip, regon)
 *   - buyer:     array (name, address, city, nip)
 *   - invoice:   array (number, issue_date, sale_date, due_date, status, payment_method, notes)
 *   - items:     array of [name, qty, unit, net_price, vat_rate (0,8,23 etc), net_total, gross_total]
 *                lub uproszczone [name, qty, unit_price, total] — wykryje VAT-owe pola po kluczach
 *   - totals:    array (net, vat, gross)
 *   - club_header_html: string
 */
class InvoicePdf
{
    /**
     * @param array<string,mixed> $data
     * @param string|null $locale 'pl'|'en' (null = current request locale).
     *                            Wszystkie wywolania __() w generatorze beda
     *                            uzywaly tego locale przez czas renderowania.
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

        $seller = $data['seller']  ?? [];
        $buyer  = $data['buyer']   ?? [];
        $inv    = $data['invoice'] ?? [];
        $items  = $data['items']   ?? [];
        $tot    = $data['totals']  ?? [];

        $number     = $e($inv['number']     ?? '—');
        $issueDate  = $e($inv['issue_date'] ?? date('Y-m-d'));
        $saleDate   = $e($inv['sale_date']  ?? ($inv['issue_date'] ?? date('Y-m-d')));
        $dueDate    = $e($inv['due_date']   ?? date('Y-m-d', strtotime('+14 days')));
        $statusKey  = (string)($inv['status'] ?? 'issued');
        $payMethod  = $e($inv['payment_method'] ?? 'przelew');
        $notes      = $e($inv['notes'] ?? '');

        $statusLabels = [
            'draft'     => ['Szkic',          '#6c757d'],
            'issued'    => ['Do zapłaty',     '#fd7e14'],
            'paid'      => ['Opłacona',       '#198754'],
            'cancelled' => ['Anulowana',      '#dc3545'],
        ];
        [$statusLabel, $statusColor] = $statusLabels[$statusKey] ?? ['Do zapłaty', '#fd7e14'];
        $statusLabel = $e($statusLabel);

        // Sprzedawca
        $sellerHtml  = '<strong>' . $e($seller['name'] ?? '') . '</strong><br>';
        $sellerHtml .= $e($seller['address'] ?? '') . '<br>';
        $sellerHtml .= $e($seller['city']    ?? '') . '<br>';
        if (!empty($seller['nip']))   $sellerHtml .= 'NIP: '   . $e($seller['nip'])   . '<br>';
        if (!empty($seller['regon'])) $sellerHtml .= 'REGON: ' . $e($seller['regon']) . '<br>';

        // Nabywca
        $buyerHtml  = '<strong>' . $e($buyer['name'] ?? '') . '</strong><br>';
        $buyerHtml .= $e($buyer['address'] ?? '') . '<br>';
        $buyerHtml .= $e($buyer['city']    ?? '') . '<br>';
        if (!empty($buyer['nip'])) $buyerHtml .= 'NIP: ' . $e($buyer['nip']) . '<br>';

        // Pozycje
        $rowsHtml = '';
        $totalNet = 0.0; $totalVat = 0.0; $totalGross = 0.0;

        if (empty($items)) {
            // Awaryjna pojedyncza pozycja na podstawie totals
            $items = [[
                'name'      => $inv['notes'] ?? 'Usługa wg umowy',
                'qty'       => 1,
                'unit'      => 'szt.',
                'net_price' => (float)($tot['net'] ?? $inv['total'] ?? 0),
                'vat_rate'  => 0,
                'net_total' => (float)($tot['net'] ?? $inv['total'] ?? 0),
                'gross_total' => (float)($tot['gross'] ?? $inv['total'] ?? 0),
            ]];
        }

        $lp = 0;
        foreach ($items as $it) {
            $lp++;
            $name      = $e($it['name'] ?? '');
            $qty       = (float)($it['qty'] ?? 1);
            $unit      = $e($it['unit'] ?? 'szt.');
            $netPrice  = (float)($it['net_price'] ?? $it['unit_price'] ?? 0);
            $vatRate   = (float)($it['vat_rate']  ?? 0);
            $netTotal  = (float)($it['net_total'] ?? ($qty * $netPrice));
            $gross     = (float)($it['gross_total'] ?? ($netTotal * (1 + $vatRate / 100)));
            $vatAmount = $gross - $netTotal;

            $totalNet   += $netTotal;
            $totalVat   += $vatAmount;
            $totalGross += $gross;

            $rowsHtml .= '<tr>'
                . '<td style="text-align:center;">' . $lp . '</td>'
                . '<td>' . $name . '</td>'
                . '<td style="text-align:center;">' . self::fmtQty($qty) . '</td>'
                . '<td style="text-align:center;">' . $unit . '</td>'
                . '<td style="text-align:right;">' . self::fmtMoney($netPrice) . '</td>'
                . '<td style="text-align:center;">' . ($vatRate > 0 ? $e((string)$vatRate) . '%' : 'zw.') . '</td>'
                . '<td style="text-align:right;">' . self::fmtMoney($netTotal) . '</td>'
                . '<td style="text-align:right;">' . self::fmtMoney($gross) . '</td>'
                . '</tr>';
        }

        // Jeśli totals podano — preferuj nad sumą obliczoną
        if (!empty($tot)) {
            $totalNet   = (float)($tot['net']   ?? $totalNet);
            $totalVat   = (float)($tot['vat']   ?? $totalVat);
            $totalGross = (float)($tot['gross'] ?? $totalGross);
        }

        $netFmt   = self::fmtMoney($totalNet);
        $vatFmt   = self::fmtMoney($totalVat);
        $grossFmt = self::fmtMoney($totalGross);
        $grossWords = $e(self::moneyInWords($totalGross));
        $genTime    = $e(date('d.m.Y H:i'));
        $notesHtml  = $notes !== '' ? '<div style="margin-top:14px;font-size:10px;color:#555;"><strong>Uwagi:</strong> ' . $notes . '</div>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="pl"><head><meta charset="UTF-8"><style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; }
  h1 { font-size: 18px; margin: 16px 0 0 0; }
  .status-badge { display:inline-block; padding:4px 10px; border-radius:3px; color:#fff; font-weight:bold; font-size:10px; background: {$statusColor}; }
  .top { width:100%; margin: 8px 0 18px 0; }
  .parties { width:100%; margin: 14px 0; }
  .parties td { width:50%; vertical-align: top; padding: 10px; border:1px solid #ddd; font-size: 10px; }
  .meta { width:100%; margin: 10px 0; border-collapse: collapse; }
  .meta td { padding: 4px 8px; font-size: 10px; }
  .meta .label { color:#666; }
  table.items { width:100%; border-collapse: collapse; margin-top: 10px; font-size: 9px; }
  table.items th, table.items td { border: 1px solid #999; padding: 5px 6px; }
  table.items th { background: #f0f0f0; font-weight: bold; }
  .totals { width: 320px; margin-left: auto; margin-top: 12px; border-collapse: collapse; font-size: 11px; }
  .totals td { padding: 4px 10px; }
  .totals .gross { background: #0d6efd; color: #fff; font-weight: bold; font-size: 13px; }
  .words { margin-top: 10px; font-size: 10px; }
  .pay-info { margin-top: 18px; font-size: 10px; }
  .footer { margin-top: 30px; font-size: 8px; color: #aaa; text-align: right; }
</style></head><body>
{$clubHeader}

<table class="top"><tr>
  <td>
    <h1>FAKTURA {$number}</h1>
  </td>
  <td style="text-align:right; vertical-align: top;">
    <span class="status-badge">{$statusLabel}</span>
  </td>
</tr></table>

<table class="parties"><tr>
  <td>
    <div style="font-size:9px; color:#666; text-transform:uppercase; letter-spacing:1px;">Sprzedawca</div>
    {$sellerHtml}
  </td>
  <td>
    <div style="font-size:9px; color:#666; text-transform:uppercase; letter-spacing:1px;">Nabywca</div>
    {$buyerHtml}
  </td>
</tr></table>

<table class="meta">
  <tr>
    <td class="label">Data wystawienia:</td><td><strong>{$issueDate}</strong></td>
    <td class="label">Data sprzedaży:</td><td><strong>{$saleDate}</strong></td>
    <td class="label">Termin płatności:</td><td><strong>{$dueDate}</strong></td>
  </tr>
</table>

<table class="items">
  <thead><tr>
    <th>Lp.</th><th>Nazwa</th><th>Ilość</th><th>J.m.</th>
    <th>Cena netto</th><th>VAT</th><th>Wartość netto</th><th>Wartość brutto</th>
  </tr></thead>
  <tbody>{$rowsHtml}</tbody>
</table>

<table class="totals">
  <tr><td>Razem netto:</td><td style="text-align:right;">{$netFmt} PLN</td></tr>
  <tr><td>VAT:</td><td style="text-align:right;">{$vatFmt} PLN</td></tr>
  <tr class="gross"><td>DO ZAPŁATY (brutto):</td><td style="text-align:right;">{$grossFmt} PLN</td></tr>
</table>

<div class="words"><strong>Słownie:</strong> {$grossWords}</div>

<div class="pay-info">
  Forma płatności: <strong>{$payMethod}</strong> &nbsp;·&nbsp;
  Termin: <strong>{$dueDate}</strong>
</div>
{$notesHtml}

<div class="footer">Wygenerowano: {$genTime}</div>
</body></html>
HTML;
    }

    public static function download(array $data, ?string $filename = null): never
    {
        $html = self::generate($data);
        $name = $filename ?? ('faktura-' . preg_replace(
            '/[^a-z0-9_\-]/i', '_', (string)($data['invoice']['number'] ?? 'FV')
        ) . '.pdf');
        PdfHelper::renderToPdf($html, $name, 'P');
        exit;
    }

    private static function fmtMoney(float $v): string
    {
        return number_format($v, 2, ',', ' ');
    }

    private static function fmtQty(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ',') ?: '0';
    }

    /**
     * Bardzo prosta wersja "słownie" — podaje sumę PLN/gr w słowach po polsku.
     * Dla minimalistycznej zgodności z FV — pełen słownik liczb wykracza poza zakres.
     */
    private static function moneyInWords(float $v): string
    {
        $zl = (int)floor($v);
        $gr = (int)round(($v - $zl) * 100);
        return $zl . ' zł ' . str_pad((string)$gr, 2, '0', STR_PAD_LEFT) . '/100';
    }
}
