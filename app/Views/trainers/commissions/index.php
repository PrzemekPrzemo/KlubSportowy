<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-cash-coin text-primary me-2"></i>
        Prowizje trenerów
    </h3>
    <div>
        <a href="<?= url('club/trainers/commissions/rates') ?>" class="btn btn-outline-primary">
            <i class="bi bi-tag"></i> Stawki
        </a>
        <a href="<?= url('club/trainers/commissions/report?year=' . (int)$year . '&month=' . (int)$month) ?>"
           class="btn btn-outline-secondary">
            <i class="bi bi-graph-up"></i> Raport
        </a>
    </div>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

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

<div class="card mb-4">
    <div class="card-header bg-light">
        <strong>Podsumowanie miesiąca <?= (int)$year ?>-<?= str_pad((string)$month, 2, '0', STR_PAD_LEFT) ?></strong>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Trener</th>
                    <th class="text-end">Wpisów</th>
                    <th class="text-end">Suma</th>
                    <th class="text-end">Naliczone</th>
                    <th class="text-end">Wypłacone</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary)): ?>
                    <tr><td colspan="5" class="text-muted text-center py-3">Brak danych za ten miesiąc.</td></tr>
                <?php else: foreach ($summary as $r): ?>
                    <tr>
                        <td><strong><?= View::e($r['full_name'] ?? $r['username']) ?></strong>
                            <small class="text-muted">@<?= View::e($r['username']) ?></small></td>
                        <td class="text-end"><?= (int)$r['items'] ?></td>
                        <td class="text-end font-monospace fw-bold"><?= format_money($r['total']) ?></td>
                        <td class="text-end text-warning"><?= format_money($r['accrued']) ?></td>
                        <td class="text-end text-success"><?= format_money($r['paid_out']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="<?= url('club/trainers/commissions/mark-paid-out') ?>">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Wpisy z miesiąca</strong>
            <button type="submit" class="btn btn-sm btn-success"
                    onclick="return confirm('Oznaczyć zaznaczone jako wypłacone?')">
                <i class="bi bi-check-circle"></i> Oznacz wybrane jako wypłacone
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" onclick="document.querySelectorAll('input[name=\'ids[]\']').forEach(c=>c.checked=this.checked)">
                        </th>
                        <th>Data wpłaty</th>
                        <th>Trener</th>
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
                        <tr><td colspan="9" class="text-center text-muted py-4">Brak naliczonych prowizji za ten okres.</td></tr>
                    <?php else: foreach ($items as $i):
                        $isAccrued = ($i['status'] ?? '') === 'accrued';
                    ?>
                        <tr>
                            <td>
                                <?php if ($isAccrued): ?>
                                    <input type="checkbox" name="ids[]" value="<?= (int)$i['id'] ?>">
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= View::e($i['payment_date']) ?></td>
                            <td><?= View::e($i['trainer_name'] ?? $i['trainer_username']) ?></td>
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
</form>
