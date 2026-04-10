<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('fees/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>">
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                        (#<?= View::e($m['member_number']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Stawka (szablon)</label>
            <select name="fee_rate_id" class="form-select">
                <option value="">— brak (opłata indywidualna) —</option>
                <?php foreach ($rates as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" data-amount="<?= (float)$r['amount'] ?>">
                        <?= View::e($r['name']) ?> (<?= format_money($r['amount']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kwota (zł) *</label>
            <input type="number" step="0.01" min="0" name="amount" id="amountInput" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Data zapłaty</label>
            <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Rok</label>
            <input type="number" name="period_year" value="<?= date('Y') ?>" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Miesiąc</label>
            <input type="number" min="1" max="12" name="period_month" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Metoda</label>
            <select name="method" class="form-select">
                <option value="przelew">przelew</option>
                <option value="gotowka">gotówka</option>
                <option value="karta">karta</option>
                <option value="blik">BLIK</option>
                <option value="inny">inny</option>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Numer referencyjny</label>
            <input type="text" name="reference" class="form-control">
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('fees') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
<script>
document.querySelector('[name=fee_rate_id]').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var amt = opt.getAttribute('data-amount');
    if (amt) document.getElementById('amountInput').value = amt;
});
</script>
