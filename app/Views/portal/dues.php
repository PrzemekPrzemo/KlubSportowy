<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Moje należności</h3>
        <p class="text-muted mb-0 small">Bieżące należności z tytułu składek klubowych i opłat dodatkowych</p>
    </div>
    <a href="<?= url('portal/fees') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-receipt"></i> Historia wpłat
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<!-- Saldo + KPI cards -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card p-3">
            <small class="text-muted">Do zapłaty (oczekujące)</small>
            <div class="fs-3 fw-bold <?= $totalOutstanding > 0 ? 'text-warning' : 'text-success' ?>">
                <?= format_money($totalOutstanding) ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 <?= $totalOverdue > 0 ? 'border-danger' : '' ?>">
            <small class="text-muted">Przeterminowane</small>
            <div class="fs-3 fw-bold text-danger">
                <?= format_money($totalOverdue) ?>
            </div>
            <?php if ($totalOverdue > 0): ?>
                <small class="text-danger">⚠ Pilna wpłata</small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <small class="text-muted">Aktywne subskrypcje</small>
            <div class="fs-3 fw-bold text-info"><?= count($assignments) ?></div>
            <small class="text-muted">
                <?= count($assignments) > 0
                    ? 'tyle stałych płatności'
                    : 'brak — skontaktuj się z klubem' ?>
            </small>
        </div>
    </div>
</div>

<!-- Aktywne subskrypcje + przypisane zniżki -->
<?php if (!empty($assignments)): ?>
<div class="card mb-3">
    <div class="card-header bg-light">
        <strong><i class="bi bi-receipt-cutoff me-1"></i> Moje stałe składki</strong>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Stawka</th>
                    <th>Okres</th>
                    <th class="text-end">Brutto</th>
                    <th>Zniżki</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $a):
                    $disc = $assignmentDiscounts[(int)$a['id']] ?? [];
                ?>
                    <tr>
                        <td><?= View::e($a['rate_name']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= View::e($a['rate_period']) ?></span></td>
                        <td class="text-end font-monospace"><?= format_money($a['rate_amount']) ?></td>
                        <td>
                            <?php if (empty($disc)): ?>
                                <span class="text-muted small">brak</span>
                            <?php else: foreach ($disc as $d): ?>
                                <span class="badge bg-success me-1" title="<?= View::e($d['description'] ?? '') ?>">
                                    <?= View::e($d['name']) ?>
                                    <?php if ($d['discount_type'] === 'percent'): ?>
                                        -<?= number_format((float)$d['value'], 2) ?>%
                                    <?php else: ?>
                                        -<?= format_money($d['value']) ?>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; endif; ?>
                        </td>
                        <td>
                            <?php if ($a['status'] === 'active'): ?>
                                <span class="badge bg-success">Aktywna</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?= View::e($a['status']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Lista należności -->
<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-list-ul me-1"></i> Należności</strong>
        <small class="text-muted"><?= count($dues) ?> wpisów</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tytuł</th>
                    <th>Okres</th>
                    <th class="text-end">Kwota</th>
                    <th class="text-end">Wpłacone</th>
                    <th>Termin</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($dues)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">
                    Nie masz żadnych należności. Dziękujemy za regularne wpłaty!
                </td></tr>
            <?php else: foreach ($dues as $d):
                $remaining     = (float)$d['net_amount'] - (float)$d['paid_amount'];
                $isOverdueLive = in_array($d['status'], ['pending','partial']) && $d['due_date'] < date('Y-m-d');
                $effectiveStatus = $isOverdueLive ? 'overdue' : $d['status'];
                $statusClass = match($effectiveStatus) {
                    'paid'      => 'success',
                    'overdue'   => 'danger',
                    'partial'   => 'warning',
                    'waived'    => 'info',
                    'cancelled' => 'secondary',
                    default     => 'warning',
                };
                $breakdown = !empty($d['discount_breakdown']) ? json_decode($d['discount_breakdown'], true) : [];
            ?>
                <tr class="<?= $isOverdueLive ? 'table-warning' : '' ?>">
                    <td>
                        <?= View::e($d['rate_name'] ?? 'Należność') ?>
                        <?php if (!empty($d['rate_fee_type'])): ?>
                            <small class="d-block text-muted"><?= View::e($d['rate_fee_type']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($breakdown)): ?>
                            <small class="d-block text-success">
                                <i class="bi bi-percent"></i>
                                <?php foreach ($breakdown as $b): ?>
                                    <?= View::e($b['name']) ?> (-<?= format_money($b['amount']) ?>)
                                    <?php if ($b !== end($breakdown)) echo '+ '; ?>
                                <?php endforeach; ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?= (int)$d['period_year'] ?><?= !empty($d['period_month']) ? '-' . str_pad((string)$d['period_month'], 2, '0', STR_PAD_LEFT) : '' ?>
                    </td>
                    <td class="text-end font-monospace fw-bold">
                        <?= format_money($d['net_amount']) ?>
                        <?php if ((float)$d['discount_amount'] > 0): ?>
                            <small class="d-block text-muted text-decoration-line-through">
                                <?= format_money($d['gross_amount']) ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td class="text-end font-monospace text-success"><?= format_money($d['paid_amount']) ?></td>
                    <td class="small">
                        <?= View::e($d['due_date']) ?>
                        <?php if ($isOverdueLive): ?>
                            <small class="d-block text-danger">⚠ minęło</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusClass ?>">
                            <?= View::e($statuses[$effectiveStatus] ?? $effectiveStatus) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (in_array($effectiveStatus, ['pending','partial','overdue']) && $remaining > 0): ?>
                            <?php if ($hasActiveGateway): ?>
                                <form method="POST" action="<?= url('portal/dues/' . (int)$d['id'] . '/pay') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-credit-card-2-front"></i>
                                        Zapłać <?= format_money($remaining) ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i>
                                    Wpłata na konto klubu
                                </small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$hasActiveGateway && $totalOutstanding > 0): ?>
<div class="alert alert-info mt-3 small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Klub nie udostępnia płatności online.</strong>
    Wpłaty należy realizować bezpośrednio na konto klubu (skontaktuj się z administracją po dane do przelewu).
</div>
<?php endif; ?>
