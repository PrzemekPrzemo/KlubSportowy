<?php
/**
 * PDF template: Financial report
 * Variables: $clubHeader, $payments, $year, $totalAmount, $generated
 */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fm = fn($v) => number_format((float)$v, 2, ',', ' ') . ' zł';
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 9px; }
    tr:nth-child(even) td { background: #f9f9f9; }
    .amount { text-align: right; font-weight: bold; }
    .total-row td { background: #ecf0f1; font-weight: bold; font-size: 10px; border-top: 2px solid #2c3e50; }
    h2 { font-size: 16px; margin: 0 0 5px 0; }
    .summary-box { background: #eaf4e8; border: 1px solid #27ae60; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
    .footer { margin-top: 15px; font-size: 8px; color: #999; text-align: right; }
</style>

<?= $clubHeader ?? '' ?>

<h2>Raport finansowy — rok <?= $e($year) ?></h2>

<div class="summary-box">
    <strong>Łączna kwota wpłat:</strong> <?= $fm($totalAmount) ?> |
    <strong>Liczba transakcji:</strong> <?= count($payments) ?>
</div>

<?php if (empty($payments)): ?>
    <p style="color: #999;">Brak zarejestrowanych płatności za rok <?= $e($year) ?>.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th style="width:30px;">Lp.</th>
            <th>Data</th>
            <th>Zawodnik</th>
            <th>Nr członkowski</th>
            <th>Typ opłaty</th>
            <th>Sport</th>
            <th>Okres</th>
            <th style="text-align:right;">Kwota</th>
        </tr>
    </thead>
    <tbody>
        <?php $lp = 0; foreach ($payments as $p): $lp++; ?>
        <tr>
            <td><?= $lp ?></td>
            <td><?= $e($p['payment_date'] ?? '') ?></td>
            <td><?= $e(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?></td>
            <td><?= $e($p['member_number'] ?? '') ?></td>
            <td><?= $e($p['fee_name'] ?? '—') ?></td>
            <td><?= $e($p['sport_name'] ?? '—') ?></td>
            <td><?= $e(($p['period_month'] ?? '') . '/' . ($p['period_year'] ?? '')) ?></td>
            <td class="amount"><?= $fm($p['amount'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="7" style="text-align:right;">RAZEM:</td>
            <td class="amount"><?= $fm($totalAmount) ?></td>
        </tr>
    </tbody>
</table>
<?php endif; ?>

<div class="footer">
    Wygenerowano: <?= $e($generated ?? '') ?>
</div>
