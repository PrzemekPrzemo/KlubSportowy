<?php
/**
 * PDF template: Zgoda na udział w treningach (Training Consent Form)
 * Variables: $clubHeader, $member, $club, $e (escape function)
 */
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
    h2 { font-size: 16px; text-align: center; margin: 15px 0 20px 0; }
    .section { margin-bottom: 15px; }
    .section-title { font-size: 12px; font-weight: bold; border-bottom: 1px solid #aaa; padding-bottom: 3px; margin-bottom: 8px; }
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .data-table td { padding: 4px 8px; border: 1px solid #ddd; font-size: 10px; }
    .data-table .label { font-weight: bold; background: #f5f5f5; width: 200px; }
    .paragraph { text-align: justify; line-height: 1.6; font-size: 10px; margin-bottom: 10px; }
    .checkbox-line { font-size: 10px; margin-bottom: 6px; padding-left: 10px; }
    .checkbox-box { display: inline-block; width: 12px; height: 12px; border: 1px solid #333; margin-right: 6px; vertical-align: middle; }
    .signature-area { margin-top: 50px; }
    .signature-line { border-top: 1px solid #333; width: 200px; display: inline-block; margin-top: 50px; text-align: center; font-size: 9px; color: #666; }
    .footer { margin-top: 30px; font-size: 8px; color: #999; text-align: right; }
</style>

<?= $clubHeader ?? '' ?>

<h2>ZGODA NA UDZIAŁ W TRENINGACH</h2>

<div class="section">
    <div class="section-title">Dane uczestnika</div>
    <table class="data-table">
        <tr>
            <td class="label">Imię i nazwisko:</td>
            <td><?= $e($member['first_name'] ?? '') ?> <?= $e($member['last_name'] ?? '') ?></td>
        </tr>
        <tr>
            <td class="label">Data urodzenia:</td>
            <td><?= $e($member['birth_date'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Numer członkowski:</td>
            <td><?= $e($member['member_number'] ?? '—') ?></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Oświadczenie</div>
    <p class="paragraph">
        Ja, niżej podpisany/a, niniejszym wyrażam zgodę na udział
        <?php if (!empty($member['birth_date']) && (date('Y') - (int)substr($member['birth_date'], 0, 4)) < 18): ?>
            mojego dziecka <strong><?= $e($member['first_name'] ?? '') ?> <?= $e($member['last_name'] ?? '') ?></strong>
        <?php else: ?>
            w
        <?php endif; ?>
        treningach organizowanych przez klub sportowy <strong><?= $e($club['name'] ?? '') ?></strong>.
    </p>

    <p class="paragraph">Oświadczam, że:</p>

    <div class="checkbox-line">
        <span class="checkbox-box"></span>
        Uczestnik nie ma przeciwwskazań zdrowotnych do uprawiania sportu / posiada
        aktualne badania lekarskie dopuszczające do udziału w treningach.
    </div>
    <div class="checkbox-line">
        <span class="checkbox-box"></span>
        Zapoznałem/am się z regulaminem treningów i zobowiązuję się do jego przestrzegania.
    </div>
    <div class="checkbox-line">
        <span class="checkbox-box"></span>
        Wyrażam zgodę na udzielenie pierwszej pomocy medycznej w razie wypadku lub kontuzji
        podczas treningu.
    </div>
    <div class="checkbox-line">
        <span class="checkbox-box"></span>
        Zostałem/am poinformowany/a o ryzyku kontuzji związanym z uprawianiem sportu
        i akceptuję to ryzyko.
    </div>
    <div class="checkbox-line">
        <span class="checkbox-box"></span>
        Wyrażam zgodę na przetwarzanie danych osobowych uczestnika w celu organizacji
        treningów, zgodnie z RODO.
    </div>
</div>

<div class="section">
    <p class="paragraph" style="font-size: 9px;">
        Data: ................................................&nbsp;&nbsp;&nbsp;&nbsp;
        Miejscowość: ................................................
    </p>
</div>

<div class="signature-area">
    <table width="100%">
        <tr>
            <td style="text-align:center; border:none;">
                <div class="signature-line">Podpis uczestnika / rodzica / opiekuna</div>
            </td>
            <td style="text-align:center; border:none;">
                <div class="signature-line">Podpis trenera</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    Wygenerowano: <?= date('d.m.Y H:i') ?>
</div>
