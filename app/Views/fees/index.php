<?php
use App\Helpers\View;

// Phase 2 KSeF — sprawdź czy klub ma włączone faktury klubowe (per-club toggle)
$ksefEnabled = false;
try {
    if (!empty($currentClubId)) {
        $ksefEnabled = (new \App\Models\ClubKsefConfigModel())->isEnabledForClub((int)$currentClubId);
    }
} catch (\Throwable) {}
?>
<div class="card p-3 mb-3 d-flex flex-row justify-content-between align-items-center">
    <div>
        <strong>Rok:</strong> <?= (int)$year ?> •
        <strong>Suma wpływów:</strong> <?= format_money($total) ?>
    </div>
    <div>
        <a href="<?= url('fees/new') ?>" class="btn btn-success"><i class="bi bi-plus"></i> <?= __('fee.new_payment') ?></a>
        <a href="<?= url('fees/rates') ?>" class="btn btn-outline-primary"><i class="bi bi-tag"></i> <?= __('fee.rates_btn') ?></a>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th><?= __('fee.col_date') ?></th><th><?= __('fee.col_member') ?></th><th><?= __('fee.col_fee') ?></th><th><?= __('fee.col_sport') ?></th><th><?= __('fee.col_period') ?></th><th><?= __('fee.col_method') ?></th><th class="text-end"><?= __('fee.col_amount') ?></th><?php if ($ksefEnabled): ?><th></th><?php endif; ?></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="<?= $ksefEnabled ? 8 : 7 ?>" class="p-0">
                <?php
                $icon       = 'bi-cash-coin';
                $title      = 'Brak płatności';
                $message    = 'Zarejestruj pierwszą wpłatę — gotówka, przelew lub karta. Zawodnicy mogą też zapłacić online z portalu (Stripe / Przelewy24 / PayU / Tpay).';
                $actionUrl  = url('fees/new');
                $actionLabel= '+ Nowa wpłata';
                include __DIR__ . '/../_partials/empty_state.php';
                ?>
            </td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $p): ?>
                <tr>
                    <td><?= format_date($p['payment_date']) ?></td>
                    <td>
                        <a href="<?= url('members/' . (int)$p['member_id']) ?>">
                            <?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?>
                        </a>
                        <small class="text-muted d-block">#<?= View::e($p['member_number']) ?></small>
                    </td>
                    <td><?= View::e($p['fee_name'] ?? '—') ?></td>
                    <td><?= View::e($p['sport_name'] ?? '—') ?></td>
                    <td><?= (int)$p['period_year'] ?><?= $p['period_month'] ? '-' . str_pad((string)$p['period_month'],2,'0',STR_PAD_LEFT) : '' ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($p['method']) ?></span></td>
                    <td class="text-end"><strong><?= format_money($p['amount']) ?></strong></td>
                    <?php if ($ksefEnabled): ?>
                    <td class="text-end">
                        <a href="<?= url('club/invoices/from-payment/' . (int)$p['id']) ?>"
                           class="btn btn-sm btn-outline-primary" title="Wystaw fakturę">
                            <i class="bi bi-receipt"></i>
                        </a>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
