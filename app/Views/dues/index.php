<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-clock-history text-primary me-2"></i>
        Należności
    </h3>
    <div class="d-flex gap-2">
        <div class="btn-group">
            <a href="<?= url('fees') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-receipt"></i> Wpłaty
            </a>
            <a href="<?= url('fees/rates') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-cash-stack"></i> Stawki
            </a>
            <a href="<?= url('fees/discounts') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-percent"></i> Zniżki
            </a>
            <a href="<?= url('fees/assignments') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-people-fill"></i> Subskrypcje
            </a>
            <a href="<?= url('fees/dues') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Należności
            </a>
            <a href="<?= url('accounting') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-journal-text"></i> Księgowość
            </a>
        </div>
        <a href="<?= url('fees/dues/generate') ?>" class="btn btn-success btn-sm">
            <i class="bi bi-magic"></i> Generuj
        </a>
    </div>
</div>

<!-- Saldo klubu -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Wpłacone (suma)</small>
            <div class="fs-4 fw-bold text-success"><?= format_money($balance['total_paid'] ?? 0) ?></div>
            <small class="text-muted"><?= (int)($balance['count_paid'] ?? 0) ?> należności</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Do zapłaty (oczekujące)</small>
            <div class="fs-4 fw-bold text-warning"><?= format_money($balance['total_outstanding'] ?? 0) ?></div>
            <small class="text-muted"><?= (int)($balance['count_outstanding'] ?? 0) ?> należności</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 <?= ($balance['total_overdue'] ?? 0) > 0 ? 'border-danger' : '' ?>">
            <small class="text-muted">Przeterminowane</small>
            <div class="fs-4 fw-bold text-danger"><?= format_money($balance['total_overdue'] ?? 0) ?></div>
            <small class="text-muted"><?= (int)($balance['count_overdue'] ?? 0) ?> należności</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <form method="POST" action="<?= url('fees/dues/refresh') ?>">
                <?= csrf_field() ?>
                <small class="text-muted">Aktualizacja statusów</small>
                <button class="btn btn-sm btn-outline-primary mt-2 w-100">
                    <i class="bi bi-arrow-clockwise"></i> Odśwież overdue
                </button>
                <small class="text-muted d-block mt-1">Przelicz status wszystkich</small>
            </form>
        </div>
    </div>
</div>

<!-- Filtry -->
<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">— wszystkie —</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Rok</label>
            <input type="number" name="period_year" class="form-control form-control-sm"
                   value="<?= View::e((string)($filters['period_year'] ?? '')) ?>"
                   min="2020" max="2099" placeholder="np. 2026">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Miesiąc</label>
            <select name="period_month" class="form-select form-select-sm">
                <option value="">—</option>
                <?php foreach (range(1, 12) as $m): ?>
                    <option value="<?= $m ?>" <?= (int)($filters['period_month'] ?? 0) === $m ? 'selected' : '' ?>>
                        <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 form-check pt-4 ps-4">
            <input type="checkbox" name="overdue_only" value="1" id="ovrChk"
                   class="form-check-input"
                   <?= !empty($filters['overdue_only']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="ovrChk">Tylko przeterminowane</label>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary btn-sm w-100">
                <i class="bi bi-funnel"></i> Filtruj
            </button>
            <a href="<?= url('fees/dues') ?>" class="btn btn-link btn-sm w-100 mt-1">wyczyść filtry</a>
        </div>
    </div>
</form>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Stawka</th>
                    <th>Okres</th>
                    <th class="text-end">Brutto</th>
                    <th class="text-end">Zniżki</th>
                    <th class="text-end">Do zapłaty</th>
                    <th class="text-end">Wpłacone</th>
                    <th>Termin</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($dues)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">
                    Brak należności pasujących do filtrów.
                    <a href="<?= url('fees/dues/generate') ?>">Wygeneruj nowe</a>
                    z aktywnych subskrypcji.
                </td></tr>
            <?php else: foreach ($dues as $d):
                $remaining = (float)$d['net_amount'] - (float)$d['paid_amount'];
                $isOverdueLive = in_array($d['status'], ['pending','partial']) && $d['due_date'] < date('Y-m-d');
                $statusClass = match(true) {
                    $d['status'] === 'paid'      => 'success',
                    $d['status'] === 'overdue'   => 'danger',
                    $d['status'] === 'partial'   => 'warning',
                    $d['status'] === 'waived'    => 'info',
                    $d['status'] === 'cancelled' => 'secondary',
                    $isOverdueLive               => 'danger',
                    default                       => 'warning',
                };
                $statusLabel = $isOverdueLive && in_array($d['status'], ['pending','partial'])
                    ? 'Przeterminowana (' . $statuses[$d['status']] . ')'
                    : $statuses[$d['status']];
            ?>
                <tr class="<?= $isOverdueLive ? 'table-warning' : '' ?>">
                    <td>
                        <strong><?= View::e($d['last_name']) ?> <?= View::e($d['first_name']) ?></strong>
                        <small class="d-block text-muted">#<?= View::e($d['member_number'] ?? '?') ?></small>
                    </td>
                    <td>
                        <small class="d-block"><?= View::e($d['rate_name'] ?? '—') ?></small>
                        <small class="text-muted"><?= View::e($d['rate_fee_type'] ?? '') ?></small>
                    </td>
                    <td class="small">
                        <?= (int)$d['period_year'] ?><?= !empty($d['period_month']) ? '-' . str_pad((string)$d['period_month'], 2, '0', STR_PAD_LEFT) : '' ?>
                    </td>
                    <td class="text-end font-monospace"><?= format_money($d['gross_amount']) ?></td>
                    <td class="text-end font-monospace text-success">
                        <?= (float)$d['discount_amount'] > 0 ? '-' . format_money($d['discount_amount']) : '—' ?>
                    </td>
                    <td class="text-end font-monospace fw-bold"><?= format_money($d['net_amount']) ?></td>
                    <td class="text-end font-monospace text-success"><?= format_money($d['paid_amount']) ?></td>
                    <td class="small">
                        <?= View::e($d['due_date']) ?>
                    </td>
                    <td><span class="badge bg-<?= $statusClass ?>"><?= View::e($statusLabel) ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if (in_array($d['status'], ['pending','partial','overdue'])): ?>
                                <button type="button" class="btn btn-sm btn-success"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#payRow<?= (int)$d['id'] ?>"
                                        title="Zarejestruj wpłatę">
                                    <i class="bi bi-cash-coin"></i>
                                </button>
                                <form method="POST" action="<?= url('fees/dues/' . (int)$d['id'] . '/waive') ?>"
                                      onsubmit="return confirm('Zwolnić zawodnika z tej należności?')" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-info" title="Zwolnij (waive)">
                                        <i class="bi bi-gift"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?= url('fees/dues/' . (int)$d['id'] . '/cancel') ?>"
                                      onsubmit="return confirm('Anulować należność?')" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary" title="Anuluj">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php if (in_array($d['status'], ['pending','partial','overdue'])): ?>
                <tr class="collapse" id="payRow<?= (int)$d['id'] ?>">
                    <td colspan="10" class="bg-light p-3">
                        <form method="POST" action="<?= url('fees/dues/' . (int)$d['id'] . '/pay') ?>" class="row g-2 align-items-end">
                            <?= csrf_field() ?>
                            <div class="col-md-2">
                                <label class="form-label small">Kwota *</label>
                                <input type="number" name="amount" step="0.01" min="0.01"
                                       max="<?= $remaining ?>"
                                       value="<?= number_format($remaining, 2, '.', '') ?>"
                                       class="form-control form-control-sm" required>
                                <small class="text-muted">do spłaty: <?= format_money($remaining) ?></small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Metoda</label>
                                <select name="method" class="form-select form-select-sm">
                                    <option value="przelew">Przelew</option>
                                    <option value="gotowka">Gotówka</option>
                                    <option value="karta">Karta</option>
                                    <option value="blik">BLIK</option>
                                    <option value="inny">Inny</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Numer referencyjny</label>
                                <input type="text" name="reference" class="form-control form-control-sm" maxlength="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Notatki</label>
                                <input type="text" name="notes" class="form-control form-control-sm" maxlength="200">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-check2"></i> Zapisz wpłatę
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
