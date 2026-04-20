<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Dziennik zdarzeń bezpieczeństwa</h4>
        <small class="text-muted">Logowania, CSRF, rate-limit, impersonacja.</small>
    </div>
    <a href="<?= url('admin/security/blocked-ips') ?>" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-ban"></i> Podejrzane IP
    </a>
</div>

<?php
$typeColors = [
    'login_failed'        => 'danger',
    'login_success'       => 'success',
    'login_2fa_failed'    => 'warning',
    'logout'              => 'secondary',
    'csrf_violation'      => 'danger',
    'rate_limit_hit'      => 'warning',
    'password_change'     => 'info',
    '2fa_enabled'         => 'primary',
    '2fa_disabled'        => 'dark',
    'impersonation_start' => 'warning',
    'impersonation_stop'  => 'info',
    'account_locked'      => 'danger',
];
?>

<?php if (!empty($stats)): ?>
<div class="card p-3 mb-3">
    <div class="small text-muted mb-2">Ostatnie 24h:</div>
    <div>
        <?php foreach ($stats as $t => $c):
            $color = $typeColors[$t] ?? 'secondary';
        ?>
            <span class="badge bg-<?= $color ?> me-1 mb-1"><?= View::e($t) ?>: <?= (int)$c ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Typ zdarzenia</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">— Wszystkie —</option>
                <?php foreach (array_keys($typeColors) as $t): ?>
                    <option value="<?= $t ?>" <?= ($filter['type'] ?? '') === $t ? 'selected' : '' ?>><?= View::e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">IP</label>
            <input type="text" name="ip" value="<?= View::e($filter['ip'] ?? '') ?>" class="form-control form-control-sm" placeholder="127.0.0.1">
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
                    <th>Czas</th><th>Typ</th><th>Użytkownik</th><th>IP</th>
                    <th>URL</th><th>User agent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Brak zdarzeń w wybranym zakresie.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagination['data'] as $r):
                        $color = $typeColors[$r['event_type']] ?? 'secondary';
                    ?>
                    <tr>
                        <td><small><?= format_datetime($r['created_at']) ?></small></td>
                        <td><span class="badge bg-<?= $color ?>"><?= View::e($r['event_type']) ?></span></td>
                        <td><small><?= View::e($r['full_name'] ?? $r['username'] ?? '—') ?></small></td>
                        <td><small><code><?= View::e($r['ip_address'] ?? '') ?></code></small></td>
                        <td class="text-truncate" style="max-width:280px;"><small class="text-muted"><?= View::e($r['url'] ?? '') ?></small></td>
                        <td class="text-truncate" style="max-width:280px;"><small class="text-muted"><?= View::e($r['user_agent'] ?? '') ?></small></td>
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
            'type' => $filter['type'] ?? '',
            'ip'   => $filter['ip'] ?? '',
            'from' => $filter['from'] ?? '',
            'to'   => $filter['to'] ?? '',
        ]));
        for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $qs ? '&' . $qs : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
