<?php
/**
 * PDF template: Event protocol
 * Variables: $clubHeader, $event, $entries, $generated
 */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) td { background: #f9f9f9; }
    h2 { font-size: 16px; margin: 0 0 5px 0; }
    .meta-table { width: 100%; margin-bottom: 15px; }
    .meta-table td { padding: 4px 8px; border: none; font-size: 11px; }
    .meta-label { font-weight: bold; color: #555; width: 160px; }
    .section-title { font-size: 13px; font-weight: bold; margin-top: 20px; margin-bottom: 5px; border-bottom: 1px solid #aaa; padding-bottom: 3px; }
    .footer { margin-top: 30px; font-size: 8px; color: #999; text-align: right; }
    .signature-area { margin-top: 50px; }
    .signature-line { border-top: 1px solid #333; width: 200px; display: inline-block; margin-top: 40px; text-align: center; font-size: 9px; color: #666; }
</style>

<?= $clubHeader ?? '' ?>

<h2>Protokół wydarzenia</h2>

<table class="meta-table">
    <tr>
        <td class="meta-label">Nazwa:</td>
        <td><?= $e($event['name'] ?? '') ?></td>
    </tr>
    <tr>
        <td class="meta-label">Typ:</td>
        <td><?= $e($event['type'] ?? '') ?></td>
    </tr>
    <tr>
        <td class="meta-label">Data rozpoczęcia:</td>
        <td><?= $e($event['event_date'] ?? '') ?></td>
    </tr>
    <?php if (!empty($event['end_date'])): ?>
    <tr>
        <td class="meta-label">Data zakończenia:</td>
        <td><?= $e($event['end_date']) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="meta-label">Miejsce:</td>
        <td><?= $e($event['location'] ?? '—') ?></td>
    </tr>
    <?php if (!empty($event['description'])): ?>
    <tr>
        <td class="meta-label">Opis:</td>
        <td><?= $e($event['description']) ?></td>
    </tr>
    <?php endif; ?>
</table>

<div class="section-title">Lista uczestników</div>

<?php if (empty($entries)): ?>
    <p style="color:#999;">Brak zarejestrowanych uczestników dla tego wydarzenia.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th style="width:30px;">Lp.</th>
            <th>Nr członkowski</th>
            <th>Nazwisko</th>
            <th>Imię</th>
            <th>Podpis</th>
        </tr>
    </thead>
    <tbody>
        <?php $lp = 0; foreach ($entries as $entry): $lp++; ?>
        <tr>
            <td><?= $lp ?></td>
            <td><?= $e($entry['member_number'] ?? '') ?></td>
            <td><?= $e($entry['last_name'] ?? '') ?></td>
            <td><?= $e($entry['first_name'] ?? '') ?></td>
            <td style="width:150px;"></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="signature-area">
    <table width="100%">
        <tr>
            <td style="text-align:center; border:none;">
                <div class="signature-line">Podpis organizatora</div>
            </td>
            <td style="text-align:center; border:none;">
                <div class="signature-line">Podpis sędziego</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    Wygenerowano: <?= $e($generated ?? '') ?>
</div>
