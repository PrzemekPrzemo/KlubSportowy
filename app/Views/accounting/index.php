<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-journal-text text-primary me-2"></i>
        Księgowość
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
            <a href="<?= url('fees/dues') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Należności
            </a>
            <a href="<?= url('accounting') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-journal-text"></i> Księgowość
            </a>
        </div>
        <a href="<?= url('accounting/export?' . http_build_query($filters)) ?>"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet"></i> Eksportuj CSV
        </a>
    </div>
</div>

<!-- Saldo + szybkie totals -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Suma wpłat (filtrowane)</small>
            <div class="fs-4 fw-bold"><?= format_money($totals['total']) ?></div>
            <small class="text-muted"><?= (int)$totals['count'] ?> wpłat</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Wpłacone w klubie (cały okres)</small>
            <div class="fs-4 fw-bold text-success"><?= format_money($balance['total_paid']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 <?= ($balance['total_outstanding'] ?? 0) > 0 ? 'border-warning' : '' ?>">
            <small class="text-muted">Saldo zaległości</small>
            <div class="fs-4 fw-bold text-warning"><?= format_money($balance['total_outstanding']) ?></div>
            <small class="text-muted">w tym przeterminowane: <?= format_money($balance['total_overdue']) ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Top metoda płatności</small>
            <?php
                $topMethod = '';
                $topAmount = 0.0;
                foreach ($totals['byMethod'] as $m => $a) {
                    if ($a > $topAmount) { $topMethod = $m; $topAmount = $a; }
                }
            ?>
            <div class="fs-4 fw-bold"><?= View::e(ucfirst($topMethod)) ?: '—' ?></div>
            <small class="text-muted"><?= format_money($topAmount) ?></small>
        </div>
    </div>
</div>

<!-- Filtry -->
<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small">Rok</label>
            <select name="year" class="form-select form-select-sm">
                <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $filters['year'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Miesiąc</label>
            <select name="month" class="form-select form-select-sm">
                <option value="">— cały rok —</option>
                <?php foreach (range(1, 12) as $m): ?>
                    <option value="<?= $m ?>" <?= $filters['month'] === $m ? 'selected' : '' ?>>
                        <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Metoda</label>
            <select name="method" class="form-select form-select-sm">
                <option value="">— wszystkie —</option>
                <?php foreach (['gotowka' => 'Gotówka', 'przelew' => 'Przelew', 'karta' => 'Karta', 'blik' => 'BLIK', 'inny' => 'Inny'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($filters['method'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">— wszystkie —</option>
                <?php foreach (['completed' => 'Zakończona', 'partial' => 'Częściowa', 'refund' => 'Refund', 'cancelled' => 'Anulowana'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary btn-sm w-100">
                <i class="bi bi-funnel"></i> Filtruj
            </button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Zawodnik</th>
                    <th>Sport</th>
                    <th>Stawka</th>
                    <th>Okres</th>
                    <th class="text-end">Kwota</th>
                    <th>Metoda</th>
                    <th>Referencja</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak wpłat dla aktualnych filtrów.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td class="small font-monospace"><?= View::e($r['payment_date']) ?></td>
                    <td>
                        <strong><?= View::e($r['last_name'] ?? '') ?> <?= View::e($r['first_name'] ?? '') ?></strong>
                        <small class="d-block text-muted">#<?= View::e($r['member_number'] ?? '?') ?></small>
                    </td>
                    <td><small><?= View::e($r['sport_name'] ?? '—') ?></small></td>
                    <td><small><?= View::e($r['rate_name'] ?? '—') ?></small></td>
                    <td class="small">
                        <?= (int)$r['period_year'] ?><?= !empty($r['period_month']) ? '-' . str_pad((string)$r['period_month'], 2, '0', STR_PAD_LEFT) : '' ?>
                    </td>
                    <td class="text-end font-monospace fw-bold"><?= format_money($r['amount']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= View::e($r['method']) ?></span></td>
                    <td class="small text-muted"><?= View::e($r['reference'] ?? '') ?></td>
                    <td>
                        <?php $st = $r['status'] ?? 'completed'; ?>
                        <span class="badge bg-<?= $st === 'completed' ? 'success' : ($st === 'refund' ? 'danger' : 'warning') ?>">
                            <?= View::e($st) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
            <tfoot class="table-light">
                <tr>
                    <td colspan="5" class="text-end fw-bold">Razem:</td>
                    <td class="text-end font-monospace fw-bold"><?= format_money($totals['total']) ?></td>
                    <td colspan="3"><small class="text-muted"><?= (int)$totals['count'] ?> wpłat</small></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
