<?php
/**
 * PDF template: Members list
 * Variables: $clubHeader, $members, $generated
 */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 9px; }
    tr:nth-child(even) td { background: #f9f9f9; }
    .status-active { color: #27ae60; font-weight: bold; }
    .status-inactive { color: #e74c3c; }
    h2 { font-size: 16px; margin: 0 0 5px 0; }
    .footer { margin-top: 15px; font-size: 8px; color: #999; text-align: right; }
</style>

<?= $clubHeader ?? '' ?>

<h2>Lista zawodników klubu</h2>
<p style="font-size: 10px; color: #666;">Łącznie: <?= count($members) ?> zawodników</p>

<table>
    <thead>
        <tr>
            <th style="width:30px;">Lp.</th>
            <th>Nr</th>
            <th>Nazwisko</th>
            <th>Imię</th>
            <th>E-mail</th>
            <th>Telefon</th>
            <th>Data ur.</th>
            <th>Status</th>
            <th>Sekcje sportowe</th>
            <th>Dołączył(a)</th>
        </tr>
    </thead>
    <tbody>
        <?php $lp = 0; foreach ($members as $m): $lp++; ?>
        <tr>
            <td><?= $lp ?></td>
            <td><?= $e($m['member_number'] ?? '') ?></td>
            <td><strong><?= $e($m['last_name'] ?? '') ?></strong></td>
            <td><?= $e($m['first_name'] ?? '') ?></td>
            <td><?= $e($m['email'] ?? '') ?></td>
            <td><?= $e($m['phone'] ?? '') ?></td>
            <td><?= $e($m['birth_date'] ?? '') ?></td>
            <td class="<?= ($m['status'] ?? '') === 'active' ? 'status-active' : 'status-inactive' ?>">
                <?= $e($m['status'] ?? '') ?>
            </td>
            <td>
                <?php
                $sportNames = array_map(fn($s) => $s['sport_name'] ?? '', $m['sports'] ?? []);
                echo $e(implode(', ', $sportNames));
                ?>
            </td>
            <td><?= $e($m['join_date'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="footer">
    Wygenerowano: <?= $e($generated ?? '') ?>
</div>
