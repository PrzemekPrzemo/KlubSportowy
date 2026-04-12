<?php use App\Helpers\View; ?>

<?php if (!empty($pending)): ?>
<div class="alert alert-warning">
    <strong>Oczekujące płatności (<?= count($pending) ?>):</strong>
    <?php foreach ($pending as $p): ?>
        <div class="small"><?= View::e($p['description']) ?> — <?= format_money($p['amount']) ?>
            <?php if ($p['checkout_url']): ?>
                <a href="<?= View::e($p['checkout_url']) ?>" class="btn btn-sm btn-warning ms-2">Zapłać teraz</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card p-3">
            <h5><i class="bi bi-credit-card"></i> Opłać składkę</h5>
            <form method="POST" action="<?= url('portal/payments/pay') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small">Rodzaj opłaty</label>
                    <select name="fee_rate_id" class="form-select" id="feeSelect">
                        <option value="">— kwota indywidualna —</option>
                        <?php foreach ($rates as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" data-amount="<?= (float)$r['amount'] ?>" data-name="<?= View::e($r['name']) ?>">
                                <?= View::e($r['name']) ?> (<?= format_money($r['amount']) ?> / <?= View::e($r['period']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Kwota (PLN)</label>
                    <input type="number" step="0.01" min="1" name="amount" id="amountField" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Opis</label>
                    <input type="text" name="description" id="descField" value="Składka klubowa" class="form-control">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small">Rok</label>
                        <input type="number" name="period_year" value="<?= date('Y') ?>" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Miesiąc</label>
                        <input type="number" name="period_month" min="1" max="12" class="form-control" placeholder="opcjonalnie">
                    </div>
                </div>
                <button class="btn btn-success w-100">
                    <i class="bi bi-lock"></i> Przejdź do płatności
                </button>
                <small class="text-muted d-block mt-2 text-center">Bezpieczna płatność kartą lub BLIK</small>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card p-3">
            <h5>Historia płatności online</h5>
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Data</th><th>Opis</th><th class="text-end">Kwota</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($history['data'])): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Brak płatności.</td></tr>
                <?php else: foreach ($history['data'] as $h):
                    $cls = match($h['status']) { 'paid'=>'success', 'failed'=>'danger', 'cancelled'=>'secondary', 'refunded'=>'info', default=>'warning' };
                ?>
                    <tr>
                        <td><small><?= format_datetime($h['created_at']) ?></small></td>
                        <td><small><?= View::e($h['description']) ?></small></td>
                        <td class="text-end"><strong><?= format_money($h['amount']) ?></strong></td>
                        <td><span class="badge bg-<?= $cls ?>"><?= View::e($h['status']) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('feeSelect').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var amt = opt.getAttribute('data-amount');
    var name = opt.getAttribute('data-name');
    if (amt) document.getElementById('amountField').value = amt;
    if (name) document.getElementById('descField').value = name;
});
</script>
