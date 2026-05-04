<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-receipt me-2"></i>Faktury</h4>
        <small class="text-muted">Subskrypcje SaaS — faktury dla klubów.</small>
    </div>
    <a href="<?= url('admin/invoices/create') ?>" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle"></i> Nowa faktura
    </a>
</div>

<?php $statusColors = ['draft' => 'secondary', 'issued' => 'primary', 'paid' => 'success', 'cancelled' => 'dark']; ?>

<div class="row g-2 mb-3">
    <?php foreach ($sums as $st => $agg):
        $color = $statusColors[$st] ?? 'secondary';
    ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body py-2">
                <div class="small text-muted"><span class="badge bg-<?= $color ?>"><?= View::e($st) ?></span></div>
                <div class="h5 mb-0"><?= number_format($agg['total'], 2, ',', ' ') ?> zł</div>
                <div class="small text-muted"><?= (int)$agg['c'] ?> faktur</div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Klub</label>
            <select name="club_id" class="form-select form-select-sm">
                <option value="">— Wszystkie —</option>
                <?php foreach ($clubs as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)($filter['clubId'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= View::e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">—</option>
                <?php foreach (array_keys($statusColors) as $s): ?>
                    <option value="<?= $s ?>" <?= ($filter['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Od</label>
            <input type="date" name="from" value="<?= View::e($filter['from'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Do</label>
            <input type="date" name="to" value="<?= View::e($filter['to'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtruj</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Numer</th><th>Klub</th><th>Wystawiono</th><th>Termin</th>
                    <th class="text-end">Kwota</th><th>Status</th><th>Zapłacono</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak faktur dla wybranych filtrów.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagination['data'] as $r):
                        $color = $statusColors[$r['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td><code><?= View::e($r['number']) ?></code></td>
                        <td><?= View::e($r['club_name'] ?? '—') ?></td>
                        <td><small><?= View::e($r['issue_date']) ?></small></td>
                        <td><small><?= View::e($r['due_date']) ?></small></td>
                        <td class="text-end"><strong><?= number_format((float)$r['total'], 2, ',', ' ') ?> zł</strong></td>
                        <td><span class="badge bg-<?= $color ?>"><?= View::e($r['status']) ?></span></td>
                        <td><small><?= $r['paid_at'] ? format_datetime($r['paid_at']) : '—' ?></small></td>
                        <td><a href="<?= url('admin/invoices/' . (int)$r['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pagination['last_page'] ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php
        $qs = http_build_query(array_filter([
            'club_id' => $filter['clubId'] ?? '',
            'status'  => $filter['status'] ?? '',
            'from'    => $filter['from'] ?? '',
            'to'      => $filter['to'] ?? '',
        ]));
        for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $qs ? '&' . $qs : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
