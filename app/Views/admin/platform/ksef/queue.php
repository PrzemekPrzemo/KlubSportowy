<?php
/**
 * Super admin: kolejka wysylki KSeF (Phase 3) across wszystkich klubow.
 *
 * @var array<int,array<string,mixed>> $rows
 * @var array<string,int|float>        $stats
 * @var array<string,?string>          $filters
 */
use App\Helpers\View;
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-0"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>KSeF — kolejka wysylki</h3>
        <small class="text-muted">Phase 3: monitorowanie XAdES sign + dispatch + UPO retrieval</small>
    </div>
    <div>
        <a href="<?= url('admin/platform/ksef') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Konfiguracja klubow
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
<?php if ($flashWarning ?? null): ?>
    <div class="alert alert-warning alert-dismissible fade show"><?= View::e($flashWarning) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-secondary">
            <div class="card-body text-center">
                <div class="text-muted small">W kolejce / w trakcie</div>
                <div class="display-6"><?= (int)($stats['queued'] + $stats['signing'] + $stats['sending'] + $stats['awaiting']) ?></div>
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
                <div class="text-muted small">Completed (24h)</div>
                <div class="display-6 text-success"><?= (int)$stats['completed_24h'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <div class="text-muted small">Sredni czas przetwarzania</div>
                <div class="h4 mb-0"><?= number_format((float)$stats['avg_processing_seconds'], 1) ?> s</div>
            </div>
        </div>
    </div>
</div>

<form method="GET" action="<?= url('admin/platform/ksef/queue') ?>" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">— Wszystkie statusy —</option>
            <?php foreach (['queued','signing','sending','awaiting_upo','completed','failed','retrying'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <input type="number" name="club_id" class="form-control form-control-sm" placeholder="club_id"
               value="<?= View::e((string)($filters['club_id'] ?? '')) ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-sm btn-outline-primary w-100">Filtruj</button>
    </div>
</form>

<div class="card">
    <table class="table table-hover mb-0 align-middle small">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Klub</th>
                <th>Faktura</th>
                <th>Status</th>
                <th>Prob</th>
                <th>Ostatni blad</th>
                <th>Reference KSeF</th>
                <th>Updated</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak wpisow w kolejce.</td></tr>
        <?php else: foreach ($rows as $r):
            $st  = (string)$r['status'];
            $cls = match($st) {
                'queued'      => 'secondary',
                'signing'     => 'info',
                'sending'     => 'info',
                'awaiting_upo'=> 'warning text-dark',
                'completed'   => 'success',
                'failed'      => 'danger',
                'retrying'    => 'warning text-dark',
                default       => 'secondary',
            };
        ?>
            <tr>
                <td><code>#<?= (int)$r['id'] ?></code></td>
                <td>
                    <strong><?= View::e((string)$r['club_name']) ?></strong>
                    <small class="text-muted d-block">id=<?= (int)$r['club_id'] ?></small>
                </td>
                <td>
                    <code><?= View::e((string)$r['invoice_number']) ?></code>
                    <small class="text-muted d-block"><?= number_format((float)$r['total_gross'], 2, ',', ' ') ?> PLN</small>
                </td>
                <td><span class="badge bg-<?= $cls ?>"><?= View::e($st) ?></span></td>
                <td><?= (int)$r['attempts'] ?></td>
                <td>
                    <?php if (!empty($r['last_error_message'])): ?>
                        <span title="<?= View::e((string)$r['last_error_message']) ?>">
                            <?= View::e(mb_substr((string)$r['last_error_message'], 0, 60)) ?>
                            <?= mb_strlen((string)$r['last_error_message']) > 60 ? '…' : '' ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($r['ksef_reference'])): ?>
                        <code><?= View::e((string)$r['ksef_reference']) ?></code>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><small><?= View::e((string)$r['updated_at']) ?></small></td>
                <td class="text-end">
                    <?php if (in_array($st, ['failed','retrying'], true)): ?>
                        <form method="POST" action="<?= url('admin/platform/ksef/queue/' . (int)$r['id'] . '/force-retry') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-warning"
                                    onclick="return confirm('Wymusic ponowienie wysylki?');">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (!in_array($st, ['completed','failed'], true)): ?>
                        <form method="POST" action="<?= url('admin/platform/ksef/queue/' . (int)$r['id'] . '/force-fail') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="reason" value="manual admin fail">
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Oznaczyc jako failed bez wysylki?');">
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

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Cron: <code>* * * * * /opt/plesk/php/8.3/bin/php /var/www/clubdesk/cli/ksef_send_worker.php &gt;&gt; /var/log/ksef.log 2&gt;&amp;1</code><br>
    Retry policy: 1m, 5m, 30m, 2h, 12h. Po 5 probach status &raquo;failed&laquo; wymaga reczne &raquo;force-retry&laquo;.
</div>
