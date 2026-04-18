<?php
/**
 * PDF template: Umowa członkowska (Member Agreement)
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
    .signature-area { margin-top: 50px; }
    .signature-line { border-top: 1px solid #333; width: 200px; display: inline-block; margin-top: 50px; text-align: center; font-size: 9px; color: #666; }
    .footer { margin-top: 30px; font-size: 8px; color: #999; text-align: right; }
</style>

<?= $clubHeader ?? '' ?>

<h2>UMOWA CZŁONKOWSKA</h2>

<div class="section">
    <div class="section-title">Dane członka</div>
    <table class="data-table">
        <tr>
            <td class="label">Imię i nazwisko:</td>
            <td><?= $e($member['first_name'] ?? '') ?> <?= $e($member['last_name'] ?? '') ?></td>
        </tr>
        <tr>
            <td class="label">Numer członkowski:</td>
            <td><?= $e($member['member_number'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Data urodzenia:</td>
            <td><?= $e($member['birth_date'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">PESEL:</td>
            <td><?= $e($member['pesel'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Adres e-mail:</td>
            <td><?= $e($member['email'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Telefon:</td>
            <td><?= $e($member['phone'] ?? '—') ?></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Warunki członkostwa</div>
    <p class="paragraph">
        1. Niniejsza umowa reguluje warunki członkostwa w klubie sportowym
        <strong><?= $e($club['name'] ?? '') ?></strong> (dalej: „Klub").
    </p>
    <p class="paragraph">
        2. Członek zobowiązuje się do przestrzegania statutu Klubu, regulaminu wewnętrznego
        oraz poleceń trenerów i instruktorów podczas treningów i zawodów.
    </p>
    <p class="paragraph">
        3. Członek zobowiązuje się do terminowego opłacania składek członkowskich zgodnie
        z obowiązującym cennikiem Klubu.
    </p>
    <p class="paragraph">
        4. Klub zobowiązuje się do zapewnienia warunków do uprawiania sportu zgodnie
        z obowiązującymi przepisami bezpieczeństwa oraz programem treningowym.
    </p>
    <p class="paragraph">
        5. Umowa zostaje zawarta na czas nieokreślony. Każda ze stron może ją rozwiązać
        z zachowaniem jednomiesięcznego okresu wypowiedzenia w formie pisemnej.
    </p>
    <p class="paragraph">
        6. Członek wyraża zgodę na przetwarzanie swoich danych osobowych w zakresie
        niezbędnym do realizacji celów statutowych Klubu, zgodnie z przepisami RODO.
    </p>
</div>

<div class="section">
    <p class="paragraph" style="font-size: 9px;">
        Data zawarcia umowy: ................................................&nbsp;&nbsp;&nbsp;&nbsp;
        Miejsce: ................................................
    </p>
</div>

<div class="signature-area">
    <table width="100%">
        <tr>
            <td style="text-align:center; border:none;">
                <div class="signature-line">Podpis członka / opiekuna</div>
            </td>
            <td style="text-align:center; border:none;">
                <div class="signature-line">Podpis przedstawiciela Klubu</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    Wygenerowano: <?= date('d.m.Y H:i') ?>
</div>
