<?php
/**
 * PDF template: Oświadczenie o odpowiedzialności (Liability Waiver)
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

<h2>OŚWIADCZENIE O ODPOWIEDZIALNOŚCI</h2>

<div class="section">
    <div class="section-title">Dane składającego oświadczenie</div>
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
            <td class="label">PESEL:</td>
            <td><?= $e($member['pesel'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Numer członkowski:</td>
            <td><?= $e($member['member_number'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Adres e-mail:</td>
            <td><?= $e($member['email'] ?? '—') ?></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Treść oświadczenia</div>
    <p class="paragraph">
        Ja, niżej podpisany/a, oświadczam, że biorę udział w zajęciach sportowych
        organizowanych przez klub sportowy <strong><?= $e($club['name'] ?? '') ?></strong>
        (dalej: „Klub") dobrowolnie i na własną odpowiedzialność.
    </p>
    <p class="paragraph">
        1. Jestem świadomy/a ryzyka kontuzji, urazów oraz innych nieszczęśliwych wypadków,
        które mogą wystąpić podczas treningów, zawodów sportowych oraz innych zajęć
        organizowanych przez Klub.
    </p>
    <p class="paragraph">
        2. Oświadczam, że mój stan zdrowia pozwala na uczestnictwo w zajęciach sportowych
        i nie mam przeciwwskazań lekarskich do uprawiania sportu.
    </p>
    <p class="paragraph">
        3. Zobowiązuję się do niezwłocznego informowania trenera prowadzącego zajęcia
        o wszelkich dolegliwościach zdrowotnych, które mogą mieć wpływ na bezpieczeństwo
        moje lub innych uczestników.
    </p>
    <p class="paragraph">
        4. Przyjmuję do wiadomości, że Klub nie ponosi odpowiedzialności za szkody
        powstałe w wyniku nieprzestrzegania regulaminu, poleceń trenerów lub instruktorów,
        a także za szkody wynikające z zatajenia informacji o stanie zdrowia.
    </p>
    <p class="paragraph">
        5. Zwalniając Klub z odpowiedzialności, nie zrzekam się swoich praw wynikających
        z powszechnie obowiązujących przepisów prawa.
    </p>
    <p class="paragraph">
        6. Niniejsze oświadczenie obowiązuje od dnia podpisania do momentu pisemnego
        odwołania lub zakończenia członkostwa w Klubie.
    </p>
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
                <div class="signature-line">Czytelny podpis składającego / opiekuna</div>
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
