<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-cash-coin text-primary me-2"></i>
        Moje prowizje
    </h3>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <small class="text-muted">Wpisów (<?= (int)$year ?>)</small>
            <h4 class="mb-0"><?= (int)$totals['items'] ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <small class="text-muted">Suma roczna</small>
            <h4 class="mb-0 font-monospace"><?= format_money($totals['total']) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center bg-warning bg-opacity-10">
            <small class="text-muted">Naliczone (do wypłaty)</small>
            <h4 class="mb-0 font-monospace text-warning"><?= format_money($totals['accrued']) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center bg-success bg-opacity-10">
            <small class="text-muted">Wypłacone</small>
            <h4 class="mb-0 font-monospace text-success"><?= format_money($totals['paid_out']) ?></h4>
        </div>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <label class="form-label small mb-0">Rok</label>
        <input type="number" name="year" class="form-control form-control-sm" value="<?= (int)$year ?>" min="2020" max="2099">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0">Miesiąc</label>
        <select name="month" class="form-select form-select-sm">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === (int)$month ? 'selected' : '' ?>>
                    <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto align-self-end">
        <button class="btn btn-sm btn-primary">Filtruj</button>
    </div>
</form>

<div class="card">
    <div class="card-header bg-light">
        <strong>Wpisy <?= (int)$year ?>-<?= str_pad((string)$month, 2, '0', STR_PAD_LEFT) ?></strong>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data wpłaty</th>
                    <th>Zawodnik</th>
                    <th>Typ</th>
                    <th class="text-end">Wpłata</th>
                    <th class="text-end">Stawka</th>
                    <th class="text-end">Prowizja</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        Brak naliczonych prowizji za ten miesiąc.
                    </td></tr>
                <?php else: foreach ($items as $i): ?>
                    <tr>
                        <td class="small"><?= View::e($i['payment_date']) ?></td>
                        <td><?= View::e(($i['first_name'] ?? '') . ' ' . ($i['last_name'] ?? '')) ?>
                            <small class="text-muted d-block">#<?= View::e($i['member_number'] ?? '') ?></small></td>
                        <td><span class="badge bg-secondary"><?= View::e($i['fee_type'] ?? '—') ?></span></td>
                        <td class="text-end font-monospace"><?= format_money($i['payment_amount']) ?></td>
                        <td class="text-end small">
                            <?= ($i['commission_type'] === 'percent')
                                ? number_format((float)$i['rate_value'], 2) . '%'
                                : format_money($i['rate_value']) ?>
                        </td>
                        <td class="text-end font-monospace fw-bold text-primary"><?= format_money($i['commission_amount']) ?></td>
                        <td>
                            <?php $st = $i['status'] ?? 'accrued'; ?>
                            <span class="badge bg-<?= $st === 'paid_out' ? 'success' : ($st === 'cancelled' ? 'secondary' : 'warning text-dark') ?>">
                                <?= View::e($statuses[$st] ?? $st) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
