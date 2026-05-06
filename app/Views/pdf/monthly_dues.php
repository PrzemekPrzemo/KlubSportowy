<?php
/**
 * PDF template (Y.2): Raport zaległości miesięcznych.
 * Variables: $clubHeader, $systemFooter, $dues, $totalOutstanding, $generated
 */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$today = strtotime(date('Y-m-d'));
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
    h2 { font-size: 16px; margin: 0 0 5px 0; color: #c0392b; }
    .summary { background: #fff5f5; border-left: 4px solid #c0392b; padding: 10px 15px; margin: 10px 0 15px; }
    .summary strong { font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #34495e; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 9px; }
    tr:nth-child(even) td { background: #f9f9f9; }
    .amount { text-align: right; font-family: monospace; font-weight: bold; }
    .overdue-days { color: #c0392b; font-weight: bold; }
    .status-overdue { background: #e74c3c; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 8px; }
    .status-partial { background: #f39c12; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 8px; }
    .status-pending { background: #95a5a6; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 8px; }
    tfoot td { background: #34495e; color: #fff; font-weight: bold; padding: 8px; }
</style>

<?= $clubHeader ?? '' ?>

<h2>Raport zaległości w opłatach</h2>

<div class="summary">
    <strong>Saldo zaległości łącznie: <?= number_format($totalOutstanding, 2, ',', ' ') ?> zł</strong>
    <br>Liczba pozycji do windykacji: <?= count($dues) ?>
    <br><span style="font-size:9px; color:#666;">Stan na <?= $e($generated ?? date('d.m.Y H:i')) ?></span>
</div>

<?php if (empty($dues)): ?>
    <p style="color:#27ae60; padding:15px; background:#f0fdf4; border-left:4px solid #27ae60;">
        <strong>✓ Brak zaległości.</strong> Wszyscy zawodnicy mają opłacone należności.
    </p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th style="width:30px;">Lp.</th>
            <th>Nr</th>
            <th>Nazwisko i imię</th>
            <th>Kontakt</th>
            <th>Rodzaj opłaty</th>
            <th>Okres</th>
            <th>Termin</th>
            <th>Dni po</th>
            <th class="amount">Należność</th>
            <th class="amount">Zapłacono</th>
            <th class="amount">Pozostało</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($dues as $i => $d):
            $remaining = (float)$d['net_amount'] - (float)$d['paid_amount'];
            $dueTs     = strtotime((string)($d['due_date'] ?? ''));
            $daysOver  = $dueTs > 0 ? max(0, (int)floor(($today - $dueTs) / 86400)) : 0;
            $period    = ($d['period_year'] ?? '') . (!empty($d['period_month']) ? '-' . str_pad((string)$d['period_month'], 2, '0', STR_PAD_LEFT) : '');
            $contact   = trim(($d['email'] ?? '') . ($d['phone'] ? ' / ' . $d['phone'] : ''));
        ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= $e($d['member_number'] ?? '') ?></td>
            <td><strong><?= $e(($d['last_name'] ?? '') . ' ' . ($d['first_name'] ?? '')) ?></strong></td>
            <td><?= $e($contact) ?></td>
            <td><?= $e($d['rate_name'] ?? $d['fee_type'] ?? '—') ?></td>
            <td><?= $e($period) ?></td>
            <td><?= $e($d['due_date'] ?? '—') ?></td>
            <td class="overdue-days"><?= $daysOver ?> dni</td>
            <td class="amount"><?= number_format((float)$d['net_amount'], 2, ',', ' ') ?> zł</td>
            <td class="amount"><?= number_format((float)$d['paid_amount'], 2, ',', ' ') ?> zł</td>
            <td class="amount" style="color:#c0392b;"><?= number_format($remaining, 2, ',', ' ') ?> zł</td>
            <td>
                <span class="status-<?= $e($d['status'] ?? 'pending') ?>"><?= $e($d['status'] ?? '') ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="10" style="text-align:right;">RAZEM DO WINDYKACJI:</td>
            <td class="amount"><?= number_format($totalOutstanding, 2, ',', ' ') ?> zł</td>
            <td></td>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<?= $systemFooter ?? '<div class="footer">Wygenerowano: ' . $e($generated ?? '') . '</div>' ?>
