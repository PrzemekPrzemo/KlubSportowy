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
 *
 * Wszystkie user-facing stringi przechodza przez __() / Translator::withLocale,
 * dzieki czemu faktury dla EN-speaking klientow generuja sie po angielsku.
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
            'draft'     => [__('pdf.invoice.status.draft'),     '#6c757d'],
            'issued'    => [__('pdf.invoice.status.issued'),    '#fd7e14'],
            'paid'      => [__('pdf.invoice.status.paid'),      '#198754'],
            'cancelled' => [__('pdf.invoice.status.cancelled'), '#dc3545'],
        ];
        [$statusLabel, $statusColor] = $statusLabels[$statusKey] ?? [__('pdf.invoice.status.issued'), '#fd7e14'];
        $statusLabel = $e($statusLabel);

        $nipLabel   = $e(__('pdf.invoice.label.nip'));
        $regonLabel = $e(__('pdf.invoice.label.regon'));

        // Sprzedawca
        $sellerHtml  = '<strong>' . $e($seller['name'] ?? '') . '</strong><br>';
        $sellerHtml .= $e($seller['address'] ?? '') . '<br>';
        $sellerHtml .= $e($seller['city']    ?? '') . '<br>';
        if (!empty($seller['nip']))   $sellerHtml .= $nipLabel   . ': ' . $e($seller['nip'])   . '<br>';
        if (!empty($seller['regon'])) $sellerHtml .= $regonLabel . ': ' . $e($seller['regon']) . '<br>';

        // Nabywca
        $buyerHtml  = '<strong>' . $e($buyer['name'] ?? '') . '</strong><br>';
        $buyerHtml .= $e($buyer['address'] ?? '') . '<br>';
        $buyerHtml .= $e($buyer['city']    ?? '') . '<br>';
        if (!empty($buyer['nip'])) $buyerHtml .= $nipLabel . ': ' . $e($buyer['nip']) . '<br>';

        // Pozycje
        $rowsHtml = '';
        $totalNet = 0.0; $totalVat = 0.0; $totalGross = 0.0;
        $unitDefault   = __('pdf.invoice.table.unit_default');
        $vatExemptText = __('pdf.invoice.table.vat_exempt');

        if (empty($items)) {
            // Awaryjna pojedyncza pozycja na podstawie totals
            $items = [[
                'name'      => $inv['notes'] ?? __('pdf.invoice.default_item_name'),
                'qty'       => 1,
                'unit'      => $unitDefault,
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
            $unit      = $e($it['unit'] ?? $unitDefault);
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
                . '<td style="text-align:center;">' . ($vatRate > 0 ? $e((string)$vatRate) . '%' : $e($vatExemptText)) . '</td>'
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

        // i18n labels
        $titleLabel      = $e(__('pdf.invoice.title'));
        $sellerLabel     = $e(__('pdf.invoice.label.seller'));
        $buyerLabel      = $e(__('pdf.invoice.label.buyer'));
        $issueDateLabel  = $e(__('pdf.invoice.label.issue_date'));
        $saleDateLabel   = $e(__('pdf.invoice.label.sale_date'));
        $dueDateLabel    = $e(__('pdf.invoice.label.due_date'));
        $lpLabel         = $e(__('pdf.invoice.table.lp'));
        $nameLabel       = $e(__('pdf.invoice.table.name'));
        $qtyLabel        = $e(__('pdf.invoice.table.qty'));
        $unitLabel       = $e(__('pdf.invoice.table.unit'));
        $netPriceLabel   = $e(__('pdf.invoice.table.net_price'));
        $vatLabel        = $e(__('pdf.invoice.table.vat'));
        $netTotalLabel   = $e(__('pdf.invoice.table.net_total'));
        $grossTotalLabel = $e(__('pdf.invoice.table.gross_total'));
        $totalsNetLabel  = $e(__('pdf.invoice.totals.net'));
        $totalsVatLabel  = $e(__('pdf.invoice.totals.vat'));
        $totalsGrossLabel = $e(__('pdf.invoice.totals.gross'));
        $inWordsLabel    = $e(__('pdf.invoice.label.in_words'));
        $payMethodLabel  = $e(__('pdf.invoice.label.payment_method'));
        $payDueLabel     = $e(__('pdf.invoice.label.payment_due'));
        $notesLabel      = $e(__('pdf.invoice.label.notes'));
        $generatedLabel  = $e(__('pdf.invoice.generated_at'));
        $currency        = $e(__('pdf.common.currency_pln'));
        $htmlLang        = $e(Translator::getLocale());

        $notesHtml  = $notes !== '' ? '<div style="margin-top:14px;font-size:10px;color:#555;"><strong>' . $notesLabel . ':</strong> ' . $notes . '</div>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLang}"><head><meta charset="UTF-8"><style>
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
    <h1>{$titleLabel} {$number}</h1>
  </td>
  <td style="text-align:right; vertical-align: top;">
    <span class="status-badge">{$statusLabel}</span>
  </td>
</tr></table>

<table class="parties"><tr>
  <td>
    <div style="font-size:9px; color:#666; text-transform:uppercase; letter-spacing:1px;">{$sellerLabel}</div>
    {$sellerHtml}
  </td>
  <td>
    <div style="font-size:9px; color:#666; text-transform:uppercase; letter-spacing:1px;">{$buyerLabel}</div>
    {$buyerHtml}
  </td>
</tr></table>

<table class="meta">
  <tr>
    <td class="label">{$issueDateLabel}:</td><td><strong>{$issueDate}</strong></td>
    <td class="label">{$saleDateLabel}:</td><td><strong>{$saleDate}</strong></td>
    <td class="label">{$dueDateLabel}:</td><td><strong>{$dueDate}</strong></td>
  </tr>
</table>

<table class="items">
  <thead><tr>
    <th>{$lpLabel}</th><th>{$nameLabel}</th><th>{$qtyLabel}</th><th>{$unitLabel}</th>
    <th>{$netPriceLabel}</th><th>{$vatLabel}</th><th>{$netTotalLabel}</th><th>{$grossTotalLabel}</th>
  </tr></thead>
  <tbody>{$rowsHtml}</tbody>
</table>

<table class="totals">
  <tr><td>{$totalsNetLabel}:</td><td style="text-align:right;">{$netFmt} {$currency}</td></tr>
  <tr><td>{$totalsVatLabel}:</td><td style="text-align:right;">{$vatFmt} {$currency}</td></tr>
  <tr class="gross"><td>{$totalsGrossLabel}:</td><td style="text-align:right;">{$grossFmt} {$currency}</td></tr>
</table>

<div class="words"><strong>{$inWordsLabel}:</strong> {$grossWords}</div>

<div class="pay-info">
  {$payMethodLabel}: <strong>{$payMethod}</strong> &nbsp;·&nbsp;
  {$payDueLabel}: <strong>{$dueDate}</strong>
</div>
{$notesHtml}

<div class="footer">{$generatedLabel}: {$genTime}</div>
</body></html>
HTML;
    }

    public static function download(array $data, ?string $filename = null, ?string $locale = null): never
    {
        $html = self::generate($data, $locale);
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
        return $zl . ' ' . __('pdf.common.currency_pln') . ' ' . str_pad((string)$gr, 2, '0', STR_PAD_LEFT) . '/100';
    }
}
