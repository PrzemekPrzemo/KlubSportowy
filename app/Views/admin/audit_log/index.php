<?php
use App\Helpers\View;

/** @var array $filter */
/** @var array $listing */
/** @var array $stats */
/** @var array $users */
/** @var array $actionTypes */
/** @var array $severities */
/** @var array $sources */
/** @var bool  $isPlatformView */
/** @var ?int  $clubId */
$clubs = $clubs ?? [];

$trendLabels = [];
$trendData   = [];
foreach (($stats['trend'] ?? []) as $row) {
    $trendLabels[] = (string)$row['date'];
    $trendData[]   = (int)$row['count'];
}

$severityBadge = static function (string $sev): string {
    return match ($sev) {
        'critical' => 'bg-danger',
        'warning'  => 'bg-warning text-dark',
        default    => 'bg-info text-dark',
    };
};

$sourceBadge = static function (string $s): string {
    return match ($s) {
        'sensitive_access' => 'bg-danger-subtle text-danger-emphasis',
        'tenant_access'    => 'bg-warning-subtle text-warning-emphasis',
        default            => 'bg-secondary-subtle text-secondary-emphasis',
    };
};

$actionBadge = static function (string $action): string {
    if (str_starts_with($action, 'scope_'))   return 'bg-warning text-dark';
    if (str_contains($action, '_delete'))     return 'bg-danger';
    if (str_contains($action, 'export'))      return 'bg-warning text-dark';
    if (str_contains($action, 'gdpr'))        return 'bg-danger-subtle text-danger-emphasis';
    if (str_contains($action, 'login'))       return 'bg-info text-dark';
    if (str_contains($action, 'impersonate')) return 'bg-warning text-dark';
    return 'bg-light text-dark border';
};

$qs = static function (array $extras = []) use ($filter, $isPlatformView): string {
    $base = [
        'action'   => $filter['action'],
        'user_id'  => $filter['user_id'] ?: '',
        'source'   => $filter['source'],
        'severity' => $filter['severity'],
        'days'     => $filter['days'],
        'search'   => $filter['search'],
        'per_page' => $filter['per_page'],
    ];
    if ($isPlatformView && isset($_GET['club_id'])) {
        $base['club_id'] = (int)$_GET['club_id'];
    }
    foreach ($extras as $k => $v) {
        $base[$k] = $v;
    }
    return http_build_query(array_filter($base, static fn($v) => $v !== '' && $v !== null));
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-clipboard-data me-2"></i><?= View::e($title) ?></h4>
        <small class="text-muted">
            <?php if ($isPlatformView): ?>
                Cross-club: wszystkie zdarzenia audytowe (super admin).
            <?php else: ?>
                Audyt zdarzeń w obrębie Twojego klubu — logowania, zmiany, dostęp do danych wrażliwych, eksporty.
            <?php endif; ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/audit-log/export?' . $qs()) ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet"></i> Eksport CSV
        </a>
        <?php if ($isPlatformView): ?>
            <a href="<?= url('admin/audit/access-log') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-shield-exclamation"></i> Tenant access
            </a>
            <a href="<?= url('admin/audit/isolation') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-shield-check"></i> Izolacja danych
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (($stats['critical_7d'] ?? 0) > 0): ?>
<div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>
    <div>
        Wykryto <strong><?= (int)$stats['critical_7d'] ?></strong> zdarzeń krytycznych w ostatnich 7 dniach.
        Sprawdź eksporty, usunięcia, zmiany uprawnień.
    </div>
</div>
<?php endif; ?>

<!-- Stats cards -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Zdarzenia dziś</small>
                <i class="bi bi-calendar-day text-primary"></i>
            </div>
            <div class="fs-3 fw-bold"><?= (int)($stats['today'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Critical (7 dni)</small>
                <i class="bi bi-exclamation-octagon text-danger"></i>
            </div>
            <div class="fs-3 fw-bold text-danger"><?= (int)($stats['critical_7d'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Dane wrażliwe (30 dni)</small>
                <i class="bi bi-shield-lock text-warning"></i>
            </div>
            <div class="fs-3 fw-bold"><?= (int)($stats['sensitive_30d'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Aktywni admini (7 dni)</small>
                <i class="bi bi-people text-success"></i>
            </div>
            <div class="fs-3 fw-bold"><?= (int)($stats['active_admins'] ?? 0) ?></div>
        </div>
    </div>
</div>

<?php if (!empty($trendLabels)): ?>
<div class="card p-3 mb-3">
    <small class="text-muted mb-2 d-block">Aktywność (ostatnie 14 dni)</small>
    <canvas id="auditTrend" height="60"></canvas>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <?php if ($isPlatformView): ?>
            <div class="col-md-2">
                <label class="form-label small">Klub</label>
                <select name="club_id" class="form-select form-select-sm">
                    <option value="">— Wszystkie —</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($clubId !== null && (int)$c['id'] === (int)$clubId) ? 'selected' : '' ?>>
                            <?= View::e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-md-2">
            <label class="form-label small">Źródło</label>
            <select name="source" class="form-select form-select-sm">
                <option value="">Wszystkie</option>
                <?php foreach ($sources as $s): ?>
                    <option value="<?= View::e($s) ?>" <?= $filter['source'] === $s ? 'selected' : '' ?>>
                        <?= View::e($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Severity</label>
            <select name="severity" class="form-select form-select-sm">
                <option value="">Wszystkie</option>
                <?php foreach ($severities as $sev): ?>
                    <option value="<?= View::e($sev) ?>" <?= $filter['severity'] === $sev ? 'selected' : '' ?>>
                        <?= View::e($sev) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Użytkownik</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">— Wszyscy —</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (int)$filter['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= View::e($u['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Okres</label>
            <select name="days" class="form-select form-select-sm">
                <?php
                $opts = [7 => '7 dni', 30 => '30 dni', 90 => '90 dni', 0 => 'Wszystko'];
                foreach ($opts as $val => $label):
                ?>
                    <option value="<?= (int)$val ?>" <?= (int)$filter['days'] === (int)$val ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Akcja</label>
            <input type="text" name="action" value="<?= View::e($filter['action']) ?>" class="form-control form-control-sm" placeholder="np. login, export">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Szukaj</label>
            <input type="text" name="search" value="<?= View::e($filter['search']) ?>" class="form-control form-control-sm" placeholder="po szczegółach / IP / encji">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Per page</label>
            <select name="per_page" class="form-select form-select-sm">
                <?php foreach ([25, 50, 100] as $pp): ?>
                    <option value="<?= $pp ?>" <?= (int)$filter['per_page'] === $pp ? 'selected' : '' ?>><?= $pp ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header py-2 d-flex justify-content-between">
        <small class="text-muted">
            <?= (int)$listing['total'] ?> wpisów · strona <?= (int)$listing['current_page'] ?> z <?= (int)$listing['last_page'] ?>
        </small>
        <?php if (!empty($listing['error'])): ?>
            <small class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?= View::e($listing['error']) ?></small>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Czas</th>
                    <th>Źródło</th>
                    <th>Użytkownik</th>
                    <?php if ($isPlatformView): ?><th>Klub</th><?php endif; ?>
                    <th>Akcja</th>
                    <th>Cel</th>
                    <th>Szczegóły</th>
                    <th>Severity</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($listing['data'])): ?>
                    <tr><td colspan="<?= $isPlatformView ? 10 : 9 ?>" class="text-center text-muted py-4">Brak wpisów dla wybranych filtrów.</td></tr>
                <?php else: foreach ($listing['data'] as $r): ?>
                    <?php
                    $sev      = (string)($r['severity'] ?? 'info');
                    $source   = (string)$r['source'];
                    $action   = (string)$r['action'];
                    $when     = (string)$r['occurred_at'];
                    $details  = (string)($r['details'] ?? '');
                    $detailsShort = mb_strlen($details) > 80 ? mb_substr($details, 0, 80) . '…' : $details;
                    ?>
                    <tr>
                        <td title="<?= View::e($when) ?>"><small class="text-muted"><?= View::e(format_datetime($when)) ?></small></td>
                        <td><span class="badge <?= $sourceBadge($source) ?>"><?= View::e($source) ?></span></td>
                        <td>
                            <?php if (!empty($r['user_id'])): ?>
                                <a href="<?= url('admin/users/' . (int)$r['user_id']) ?>"><?= View::e($r['username'] ?? ('user#' . (int)$r['user_id'])) ?></a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isPlatformView): ?>
                            <td><small><?= View::e($r['club_name'] ?? ($r['club_id'] !== null ? '#' . (int)$r['club_id'] : '—')) ?></small></td>
                        <?php endif; ?>
                        <td><span class="badge <?= $actionBadge($action) ?>"><?= View::e($action) ?></span></td>
                        <td>
                            <?php if (!empty($r['target_type'])): ?>
                                <small><?= View::e($r['target_type']) ?><?php if (!empty($r['target_id'])): ?> #<?= (int)$r['target_id'] ?><?php endif; ?></small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($details !== ''): ?>
                                <small title="<?= View::e($details) ?>"><?= View::e($detailsShort) ?></small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $severityBadge($sev) ?>"><?= View::e($sev) ?></span></td>
                        <td><small class="text-muted"><?= View::e($r['ip_address'] ?? '') ?></small></td>
                        <td>
                            <a href="<?= url('admin/audit-log/' . $source . '/' . (int)$r['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Szczegóły">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (((int)$listing['last_page']) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php
        $current = (int)$listing['current_page'];
        $last    = (int)$listing['last_page'];
        $from    = max(1, $current - 5);
        $to      = min($last, $current + 5);
        for ($p = $from; $p <= $to; $p++): ?>
            <li class="page-item <?= $p === $current ? 'active' : '' ?>">
                <a class="page-link" href="?<?= $qs(['page' => $p]) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php if (!empty($trendLabels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const el = document.getElementById('auditTrend');
    if (!el) return;
    new Chart(el.getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                label: 'Zdarzenia',
                data: <?= json_encode($trendData) ?>,
                tension: 0.35,
                borderColor: 'var(--app-primary, #EE2C28)',
                backgroundColor: 'rgba(238, 44, 40, 0.15)',
                fill: true,
                pointRadius: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { ticks: { maxRotation: 0, autoSkip: true } }
            }
        }
    });
})();
</script>
<?php endif; ?>
