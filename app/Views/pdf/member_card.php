<?php
/**
 * PDF template: Member card (A5 landscape)
 * Variables: $clubHeader, $member, $branding, $clubId, $generated
 */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$primary = $branding['primary_color'] ?? '#0d6efd';
$accent  = $branding['accent_color'] ?? '#198754';
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
    .card-container {
        width: 100%;
        max-width: 560px;
        border: 2px solid <?= $e($primary) ?>;
        border-radius: 10px;
        padding: 20px;
        margin: 0 auto;
        position: relative;
        background: #fff;
    }
    .card-header-bar {
        background: <?= $e($primary) ?>;
        color: #fff;
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .card-body-table { width: 100%; }
    .card-body-table td { padding: 3px 5px; vertical-align: top; border: none; }
    .photo-placeholder {
        width: 90px;
        height: 110px;
        border: 2px dashed #ccc;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #aaa;
        font-size: 9px;
        background: #fafafa;
    }
    .qr-placeholder {
        width: 80px;
        height: 80px;
        border: 2px dashed #ccc;
        border-radius: 4px;
        text-align: center;
        color: #aaa;
        font-size: 8px;
        background: #fafafa;
        padding-top: 25px;
    }
    .field-label { font-size: 8px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1px; }
    .field-value { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
    .sports-list { font-size: 9px; }
    .sport-tag {
        display: inline-block;
        background: <?= $e($accent) ?>;
        color: #fff;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 8px;
        margin: 1px 2px;
    }
    .card-footer-bar {
        border-top: 1px solid #ddd;
        margin-top: 12px;
        padding-top: 8px;
        font-size: 8px;
        color: #999;
        text-align: center;
    }
    .member-number-big {
        font-size: 16px;
        font-weight: bold;
        color: <?= $e($primary) ?>;
        letter-spacing: 1px;
    }
</style>

<div class="card-container">
    <div class="card-header-bar">
        Karta Członkowska
    </div>

    <table class="card-body-table">
        <tr>
            <!-- Photo -->
            <td style="width: 100px; vertical-align: top;">
                <div class="photo-placeholder">
                    Zdjęcie<br>zawodnika
                </div>
            </td>

            <!-- Member details -->
            <td style="vertical-align: top; padding-left: 15px;">
                <div class="field-label">Imię i nazwisko</div>
                <div class="field-value" style="font-size: 14px;">
                    <?= $e(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?>
                </div>

                <div class="field-label">Numer członkowski</div>
                <div class="member-number-big">
                    <?= $e($member['member_number'] ?? '—') ?>
                </div>

                <div style="margin-top: 6px;">
                    <div class="field-label">Data urodzenia</div>
                    <div class="field-value"><?= $e($member['birth_date'] ?? '—') ?></div>
                </div>

                <div class="field-label">Sekcje sportowe</div>
                <div class="sports-list">
                    <?php if (!empty($member['sports'])): ?>
                        <?php foreach ($member['sports'] as $s): ?>
                            <span class="sport-tag"><?= $e($s['sport_name'] ?? '') ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color:#999;">brak</span>
                    <?php endif; ?>
                </div>
            </td>

            <!-- QR code area -->
            <td style="width: 100px; text-align: right; vertical-align: top;">
                <div class="qr-placeholder">
                    [QR Code]
                </div>
                <div style="font-size: 7px; color: #aaa; text-align: center; margin-top: 4px;">
                    ID: <?= $e($member['id'] ?? '') ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="card-footer-bar">
        Dokument wygenerowany: <?= $e($generated ?? '') ?>
        &nbsp;|&nbsp;
        Status: <strong><?= $e($member['status'] ?? '—') ?></strong>
        &nbsp;|&nbsp;
        Data dołączenia: <?= $e($member['join_date'] ?? '—') ?>
    </div>
</div>
