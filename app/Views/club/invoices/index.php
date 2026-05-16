<?php
/**
 * @var array              $pagination
 * @var array              $stats
 * @var array<string,mixed> $filters
 */
use App\Helpers\View;

$statusColors = [
    'draft'          => 'secondary',
    'issued'         => 'primary',
    'sent_ksef'      => 'info',
    'accepted_ksef'  => 'success',
    'rejected_ksef'  => 'danger',
    'cancelled'      => 'dark',
];
$statusLabels = [
    'draft'         => 'Szkic',
    'issued'        => 'Wystawiona',
    'sent_ksef'     => 'Wysłana do KSeF',
    'accepted_ksef' => 'KSeF: zaakceptowana',
    'rejected_ksef' => 'KSeF: odrzucona',
    'cancelled'     => 'Anulowana',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Faktury sprzedaży</h3>
        <small class="text-muted">Wystawione przez klub. KSeF Phase 2 — wysyłka uruchomiona w Phase 3.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('club/invoices/bulk-from-payments') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-list-check"></i> Wystaw z płatności
        </a>
        <a href="<?= url('club/invoices/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nowa faktura
        </a>
    </div>
</div>

<?php foreach (['success'=>'flashSuccess','error'=>'flashError','warning'=>'flashWarning','info'=>'flashInfo'] as $cls=>$flash): if (!empty($$flash)): ?>
    <div class="alert alert-<?= $cls ?> alert-dismissible fade show"><?= View::e($$flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; endforeach; ?>

<!-- Summary cards -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="card bg-light"><div class="card-body py-2">
            <small class="text-muted d-block">Łącznie wystawione</small>
            <strong class="fs-5"><?= (int)$stats['count_total'] ?></strong>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light"><div class="card-body py-2">
            <small class="text-muted d-block">Suma brutto</small>
            <strong class="fs-5"><?= number_format((float)$stats['total_gross'], 2, ',', ' ') ?> PLN</strong>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light"><div class="card-body py-2">
            <small class="text-muted d-block">Zapłacone</small>
            <strong class="fs-5 text-success"><?= number_format((float)$stats['total_paid'], 2, ',', ' ') ?> PLN</strong>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light"><div class="card-body py-2">
            <small class="text-muted d-block">Zaległe</small>
            <strong class="fs-5 text-danger"><?= number_format((float)$stats['total_outstanding'], 2, ',', ' ') ?> PLN</strong>
        </div></div>
    </div>
</div>

<!-- Filtry -->
<form method="GET" action="<?= url('club/invoices') ?>" class="card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small">Szukaj (numer / nabywca)</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= View::e((string)($filters['q'] ?? '')) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">— wszystkie —</option>
                <?php foreach ($statusLabels as $k => $lab): ?>
                    <option value="<?= $k ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= View::e($lab) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Rok</label>
            <input type="number" name="year" class="form-control form-control-sm" min="2020" max="2099" value="<?= View::e((string)($filters['year'] ?? '')) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Od</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= View::e((string)($filters['date_from'] ?? '')) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Do</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= View::e((string)($filters['date_to'] ?? '')) ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary btn-sm w-100">Filtruj</button>
        </div>
    </div>
</form>

<!-- Tabela -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Numer</th>
                    <th>Data</th>
                    <th>Nabywca</th>
                    <th>Status</th>
                    <th class="text-end">Brutto</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pagination['data'])): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak faktur. <a href="<?= url('club/invoices/create') ?>">Wystaw pierwszą</a>.</td></tr>
            <?php else: foreach ($pagination['data'] as $row): ?>
                <tr>
                    <td><code><?= View::e($row['invoice_number']) ?></code></td>
                    <td><?= View::e($row['issue_date']) ?></td>
                    <td>
                        <?php if (!empty($row['buyer_member_id'])): ?>
                            <i class="bi bi-person-fill text-muted"></i>
                            <?= View::e(($row['buyer_last_name'] ?? '') . ' ' . ($row['buyer_first_name'] ?? '')) ?>
                            <?php if (!empty($row['member_number'])): ?>
                                <small class="text-muted">#<?= View::e($row['member_number']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= View::e($row['buyer_name']) ?>
                            <?php if (!empty($row['buyer_nip'])): ?>
                                <small class="text-muted">(NIP <?= View::e($row['buyer_nip']) ?>)</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusColors[$row['status']] ?? 'secondary' ?>">
                            <?= View::e($statusLabels[$row['status']] ?? $row['status']) ?>
                        </span>
                        <?php if (($row['payment_status'] ?? '') === 'paid'): ?>
                            <span class="badge bg-success-subtle text-success">Zapłacone</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><strong><?= number_format((float)$row['total_gross'], 2, ',', ' ') ?></strong></td>
                    <td class="text-end">
                        <a href="<?= url('club/invoices/' . (int)$row['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= url('club/invoices/' . (int)$row['id'] . '/pdf') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pagination['last_page'] ?? 1) > 1): ?>
<nav class="mt-3"><ul class="pagination pagination-sm">
    <?php for ($p = 1; $p <= (int)$pagination['last_page']; $p++):
        $params = $_GET; $params['page'] = $p; ?>
        <li class="page-item <?= $p === (int)$pagination['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query($params) ?>"><?= $p ?></a>
        </li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>
