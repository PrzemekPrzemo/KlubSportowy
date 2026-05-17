<?php use App\Helpers\View; ?>
<form method="GET" action="<?= url('admin/platform/payments/charges') ?>" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Od</label>
            <input type="date" name="from" value="<?= View::e((string)$from) ?>" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Do</label>
            <input type="date" name="to" value="<?= View::e((string)$to) ?>" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Klub</label>
            <select name="club_id" class="form-select">
                <option value="">— wszystkie —</option>
                <?php foreach ($clubs as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($clubId === (int)$c['id']) ? 'selected':'' ?>><?= View::e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filtruj</button></div>
    </div>
</form>

<div class="row mb-3">
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Brutto (suma)</div>
        <h4><?= number_format($report['total_gross']/100, 2) ?> PLN</h4></div></div>
    <div class="col-md-4"><div class="card p-3 bg-success-subtle"><div class="text-muted small">Platform fees</div>
        <h4 class="text-success"><?= number_format($report['total_fees']/100, 2) ?> PLN</h4></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Net dla klubów</div>
        <h4><?= number_format($report['total_net']/100, 2) ?> PLN</h4></div></div>
</div>

<div class="card">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr>
        <th>Data</th><th>Klub</th><th>Provider</th><th>Transakcja</th>
        <th class="text-end">Brutto</th><th class="text-end">Fee</th><th class="text-end">Net klub</th>
    </tr></thead>
    <tbody>
    <?php foreach ($report['rows'] as $row): ?>
        <tr>
            <td class="small"><?= View::e((string)$row['charged_at']) ?></td>
            <td><?= View::e((string)$row['club_name']) ?></td>
            <td><span class="badge bg-info"><?= View::e($row['provider']) ?></span></td>
            <td><code class="small"><?= View::e($row['transaction_id']) ?></code></td>
            <td class="text-end"><?= number_format(((int)$row['gross_amount_cents'])/100, 2) ?></td>
            <td class="text-end text-success"><?= number_format(((int)$row['platform_fee_cents'])/100, 2) ?></td>
            <td class="text-end"><?= number_format(((int)$row['club_net_amount_cents'])/100, 2) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($report['rows'])): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Brak transakcji w wybranym okresie</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
