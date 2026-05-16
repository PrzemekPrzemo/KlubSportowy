<?php
/**
 * @var array<int, array<string,mixed>> $payments
 */
use App\Helpers\View;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0"><i class="bi bi-list-check me-2"></i>Wystaw faktury z płatności</h3>
        <small class="text-muted">Tworzy szkice faktur (draft) dla zaznaczonych płatności. Numer FV nadasz przy wystawianiu.</small>
    </div>
    <a href="<?= url('club/invoices') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('club/invoices/bulk-from-payments/store') ?>">
    <?= csrf_field() ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="small text-muted">Płatności bez wystawionej faktury:</span>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Zaznacz wszystkie</button>
                <button class="btn btn-sm btn-primary"><i class="bi bi-check2-all"></i> Utwórz szkice</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"></th>
                        <th>Data</th>
                        <th>Członek</th>
                        <th>Składka</th>
                        <th>Okres</th>
                        <th class="text-end">Kwota</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Brak płatności bez faktury.</td></tr>
                <?php else: foreach ($payments as $p): ?>
                    <tr>
                        <td><input type="checkbox" name="payment_ids[]" value="<?= (int)$p['id'] ?>" class="form-check-input bulk-cb"></td>
                        <td><?= View::e((string)$p['payment_date']) ?></td>
                        <td>
                            <?= View::e(($p['last_name'] ?? '') . ' ' . ($p['first_name'] ?? '')) ?>
                            <small class="text-muted">#<?= View::e((string)($p['member_number'] ?? '')) ?></small>
                        </td>
                        <td><?= View::e((string)($p['fee_name'] ?? '—')) ?></td>
                        <td>
                            <?= (int)$p['period_year'] ?><?= $p['period_month'] ? '/' . str_pad((string)$p['period_month'], 2, '0', STR_PAD_LEFT) : '' ?>
                        </td>
                        <td class="text-end"><strong><?= number_format((float)$p['amount'], 2, ',', ' ') ?></strong></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
document.getElementById('selectAll').addEventListener('click', function() {
    var cbs = document.querySelectorAll('.bulk-cb');
    var allChecked = Array.from(cbs).every(function(cb){ return cb.checked; });
    cbs.forEach(function(cb){ cb.checked = !allChecked; });
});
</script>
