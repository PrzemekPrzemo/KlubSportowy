<?php
/**
 * PDF template: Custom report builder result.
 * Variables: $clubHeader, $systemFooter, $report, $result, $generated
 */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 9px; }
    tr:nth-child(even) td { background: #f9f9f9; }
    h2 { font-size: 16px; margin: 0 0 5px 0; }
    .summary-box { background: #eef2f7; border: 1px solid #2c3e50; padding: 8px 10px; margin-bottom: 10px; border-radius: 4px; }
    .footer { margin-top: 15px; font-size: 8px; color: #999; text-align: right; }
</style>

<?= $clubHeader ?? '' ?>

<h2><?= $e($report['name'] ?? 'Raport') ?></h2>
<?php if (!empty($report['description'])): ?>
    <p style="color:#666;margin:0 0 6px 0;"><?= $e($report['description']) ?></p>
<?php endif; ?>

<div class="summary-box">
    <strong>Źródło danych:</strong> <?= $e($report['data_source'] ?? '') ?> |
    <strong>Liczba wierszy:</strong> <?= (int)($result['total'] ?? 0) ?> |
    <strong>Wygenerowano:</strong> <?= $e($generated ?? '') ?>
</div>

<?php if (empty($result['rows'])): ?>
    <p style="color:#999;">Brak wyników dla tego raportu.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <?php foreach (($result['columns'] ?? []) as $c): ?>
                <th><?= $e($c['label']) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($result['rows'] as $row): ?>
            <tr>
                <?php foreach ($result['columns'] as $c): ?>
                    <td><?= $e($row[$c['key']] ?? '') ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?= $systemFooter ?? '' ?>
