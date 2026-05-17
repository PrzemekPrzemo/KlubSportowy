<?php
/**
 * Super admin: kolejka webhook deliveries cross-klub.
 *
 * @var array<int,array<string,mixed>> $rows
 * @var array<string,mixed>            $stats
 * @var array<string,?string>          $filters
 */
use App\Helpers\View;
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-0"><i class="bi bi-plug text-primary me-2"></i>Webhooks — kolejka dostarczen</h3>
        <small class="text-muted">Cross-klub: pending / retrying / failed / delivered. Worker: cli/webhook_worker.php.</small>
    </div>
    <div>
        <a href="<?= url('admin/platform/ksef/queue') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left-right"></i> KSeF queue
        </a>
    </div>
</div>

<?php if ($flashSuccess ?? null): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= View::e($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError ?? null): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= View::e($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-secondary">
            <div class="card-body text-center">
                <div class="text-muted small">Pending + retrying</div>
                <div class="display-6"><?= (int)$stats['total_pending'] + (int)$stats['total_retrying'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <div class="text-muted small">Failed (24h)</div>
                <div class="display-6 text-danger"><?= (int)$stats['failed_24h'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <div class="text-muted small">Delivered (24h)</div>
                <div class="display-6 text-success"><?= (int)$stats['delivered_24h'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <div class="text-muted small">Sredni czas dostawy</div>
                <div class="h4 mb-0"><?= number_format((float)$stats['avg_delivery_seconds'], 1) ?> s</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($stats['event_breakdown'])): ?>
<div class="card mb-3">
    <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
        <strong class="me-2 small">Top eventy (7d):</strong>
        <?php foreach ($stats['event_breakdown'] as $b): ?>
            <span class="badge bg-light text-dark border">
                <code><?= View::e((string)$b['event_type']) ?></code>
                <span class="text-muted ms-1"><?= (int)$b['c'] ?></span>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<form method="GET" action="<?= url('admin/platform/webhooks/queue') ?>" class="row g-2 mb-3">
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">— Wszystkie statusy —</option>
            <?php foreach (['pending','retrying','delivered','failed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <input type="number" name="club_id" class="form-control form-control-sm" placeholder="club_id"
               value="<?= View::e((string)($filters['club_id'] ?? '')) ?>">
    </div>
    <div class="col-md-2">
        <input type="text" name="event_type" class="form-control form-control-sm" placeholder="event_type (member.created)"
               value="<?= View::e((string)($filters['event_type'] ?? '')) ?>">
    </div>
    <div class="col-md-2">
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="<?= View::e((string)($filters['date_from'] ?? '')) ?>">
    </div>
    <div class="col-md-2">
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="<?= View::e((string)($filters['date_to'] ?? '')) ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-sm btn-outline-primary w-100">Filtruj</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle small">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Klub</th>
                <th>Event / Subskrypcja</th>
                <th>Target URL</th>
                <th>Status</th>
                <th>Prob</th>
                <th>HTTP</th>
                <th>Ostatnia proba</th>
                <th>Utworzono</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Brak wpisow w kolejce.</td></tr>
        <?php else: foreach ($rows as $r):
            $st  = (string)$r['status'];
            $cls = match ($st) {
                'pending'   => 'secondary',
                'retrying'  => 'warning text-dark',
                'delivered' => 'success',
                'failed'    => 'danger',
                default     => 'secondary',
            };
            $url = (string)$r['target_url'];
            $urlShort = mb_strlen($url) > 50 ? (mb_substr($url, 0, 47) . '…') : $url;
        ?>
            <tr>
                <td><code>#<?= (int)$r['id'] ?></code></td>
                <td>
                    <strong><?= View::e((string)($r['club_name'] ?? '—')) ?></strong>
                    <small class="text-muted d-block">id=<?= (int)$r['club_id'] ?></small>
                </td>
                <td>
                    <code><?= View::e((string)$r['event_type']) ?></code>
                    <small class="text-muted d-block"><?= View::e((string)($r['subscription_name'] ?? '')) ?></small>
                </td>
                <td title="<?= View::e($url) ?>">
                    <code class="small"><?= View::e($urlShort) ?></code>
                </td>
                <td><span class="badge bg-<?= $cls ?>"><?= View::e($st) ?></span></td>
                <td><?= (int)$r['attempts'] ?> / 5</td>
                <td>
                    <?php if (!empty($r['http_status'])): ?>
                        <span class="<?= ((int)$r['http_status'] >= 200 && (int)$r['http_status'] < 300) ? 'text-success' : 'text-danger' ?>">
                            <?= (int)$r['http_status'] ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><small>
                    <?php if (!empty($r['delivered_at'])): ?>
                        <?= View::e((string)$r['delivered_at']) ?>
                    <?php elseif (!empty($r['next_retry_at'])): ?>
                        <span class="text-muted">retry:</span> <?= View::e((string)$r['next_retry_at']) ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </small></td>
                <td><small><?= View::e((string)$r['created_at']) ?></small></td>
                <td class="text-end">
                    <?php if (in_array($st, ['failed','retrying','pending'], true)): ?>
                        <form method="POST" action="<?= url('admin/platform/webhooks/' . (int)$r['id'] . '/retry') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-warning"
                                    title="Wymus retry"
                                    onclick="return confirm('Wrocic delivery do kolejki (status=pending, attempts=0)?');">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (!in_array($st, ['delivered','failed'], true)): ?>
                        <form method="POST" action="<?= url('admin/platform/webhooks/' . (int)$r['id'] . '/fail-permanently') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="reason" value="manual admin fail-permanently">
                            <button class="btn btn-sm btn-outline-danger"
                                    title="Fail permanently"
                                    onclick="return confirm('Oznaczyc jako failed bez dalszych prob?');">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Worker: <code>cli/webhook_worker.php</code> — bierze batch 100 pending/retrying co minute, retry policy 1m/5m/30m/2h/12h (5 prob).
    Po przekroczeniu max status &raquo;failed&laquo; wymaga reczne &raquo;retry&laquo; przez ten panel.
</div>
