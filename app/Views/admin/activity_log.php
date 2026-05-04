<?php use App\Helpers\View; ?>

<div class="mb-3">
    <h4 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Log aktywności</h4>
    <small class="text-muted">Audyt akcji użytkowników (klub, akcja, IP).</small>
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
            <label class="form-label small">User ID</label>
            <input type="number" name="user_id" value="<?= View::e($filter['userId'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Akcja (LIKE)</label>
            <input type="text" list="actions-list" name="action" value="<?= View::e($filter['action'] ?? '') ?>" class="form-control form-control-sm" placeholder="np. impersonate">
            <datalist id="actions-list">
                <?php foreach (($actions ?? []) as $a): ?>
                    <option value="<?= View::e($a['action']) ?>"><?= (int)$a['c'] ?></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Od</label>
            <input type="date" name="from" value="<?= View::e($filter['from'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Do</label>
            <input type="date" name="to" value="<?= View::e($filter['to'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Czas</th><th>Użytkownik</th><th>Klub</th><th>Akcja</th>
                    <th>Encja</th><th>ID</th><th>Szczegóły</th><th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak wpisów dla wybranych filtrów.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagination['data'] as $r): ?>
                        <tr>
                            <td><small><?= format_datetime($r['created_at']) ?></small></td>
                            <td><small><?= View::e($r['full_name'] ?? $r['username'] ?? '—') ?></small></td>
                            <td><small><?= View::e($r['club_name'] ?? '—') ?></small></td>
                            <td><code><?= View::e($r['action']) ?></code></td>
                            <td><small><?= View::e($r['entity'] ?? '') ?></small></td>
                            <td><small><?= $r['entity_id'] !== null ? (int)$r['entity_id'] : '' ?></small></td>
                            <td><small><?= View::e($r['details'] ?? '') ?></small></td>
                            <td><small><?= View::e($r['ip_address'] ?? '') ?></small></td>
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
            'user_id' => $filter['userId'] ?? '',
            'action'  => $filter['action'] ?? '',
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
