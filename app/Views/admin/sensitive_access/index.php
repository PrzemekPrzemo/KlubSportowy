<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-shield-exclamation text-warning me-2"></i>Dziennik dostępu do danych wrażliwych</h4>
    <small class="text-muted">RODO art. 30 — rejestr czynności przetwarzania</small>
</div>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Każdy odczyt danych medycznych, anti-doping, pomiarów ciała, kontaktów awaryjnych i zgód
    opiekunów jest automatycznie rejestrowany. Dostęp do tego dziennika mają tylko osoby z rolą <strong>zarząd</strong>.
</div>

<?php if (!empty($topUsers)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header"><i class="bi bi-people me-1"></i> Najaktywniejsi użytkownicy (30 dni)</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Użytkownik</th><th class="text-end">Dostępy</th></tr></thead>
            <tbody>
            <?php foreach ($topUsers as $u): ?>
                <tr>
                    <td><?= View::e($u['full_name']) ?> <small class="text-muted">(<?= View::e($u['username']) ?>)</small></td>
                    <td class="text-end"><span class="badge bg-primary"><?= (int)$u['access_count'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<form method="GET" class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small">Typ danych</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Wszystkie</option>
                    <?php foreach ($dataTypes as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($filters['dataType'] ?? null) === $k ? 'selected' : '' ?>><?= View::e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Zawodnik</label>
                <select name="member" class="form-select form-select-sm">
                    <option value="">Wszyscy</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= (int)($filters['memberId'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                            <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Od</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= View::e($filters['from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Do</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= View::e($filters['to'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i> Filtruj</button>
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Czas</th><th>Użytkownik</th><th>Typ danych</th><th>Akcja</th>
                    <th>Zawodnik</th><th>Kontekst</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php $logs = $pagination['data'] ?? []; if (empty($logs)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak wpisów.</td></tr>
            <?php else: foreach ($logs as $l): ?>
                <tr>
                    <td class="small text-muted"><?= View::e($l['created_at']) ?></td>
                    <td><?= View::e($l['user_name'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($dataTypes[$l['data_type']] ?? $l['data_type']) ?></span></td>
                    <td><small><?= View::e($actions[$l['action']] ?? $l['action']) ?></small></td>
                    <td>
                        <?php if ($l['member_last']): ?>
                            <?= View::e($l['member_last'] . ' ' . $l['member_first']) ?>
                            <small class="text-muted">#<?= View::e($l['member_number']) ?></small>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small text-muted font-monospace" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= View::e($l['context'] ?? '—') ?>
                    </td>
                    <td class="small font-monospace"><?= View::e($l['ip_address'] ?? '—') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center small">
        <span>Strona <?= $pagination['current_page'] ?? 1 ?> z <?= $pagination['last_page'] ?? 1 ?></span>
        <div>
            <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                <a href="?page=<?= ($pagination['current_page'] ?? 1) - 1 ?>" class="btn btn-sm btn-outline-secondary">Poprzednia</a>
            <?php endif; ?>
            <?php if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
                <a href="?page=<?= ($pagination['current_page'] ?? 1) + 1 ?>" class="btn btn-sm btn-outline-secondary">Następna</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
