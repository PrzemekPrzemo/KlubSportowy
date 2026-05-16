<?php
/**
 * Super admin: lista wszystkich klubów + status KSeF.
 *
 * @var array<int, array<string, mixed>> $rows
 * @var string                            $filter
 */
use App\Helpers\View;
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>KSeF — zarządzanie integracją</h3>
        <small class="text-muted">Phase 1: foundation — toggle dostępu klubom + monitoring testów.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="btn-group btn-group-sm">
            <a href="<?= url('admin/platform/ksef?filter=all') ?>"
               class="btn btn-outline-secondary <?= $filter === 'all' ? 'active' : '' ?>">
                Wszystkie kluby
            </a>
            <a href="<?= url('admin/platform/ksef?filter=enabled') ?>"
               class="btn btn-outline-secondary <?= $filter === 'enabled' ? 'active' : '' ?>">
                Tylko aktywne
            </a>
        </div>
        <a href="<?= url('admin/platform/ksef/queue') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-cloud-arrow-up"></i> Kolejka wysylki
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

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Phase 1 — foundation.</strong> Włączenie KSeF dla klubu udostępnia jego administratorom
    formularz konfiguracyjny (<code>/club/ksef-settings</code>). Wystawianie faktur (Phase 2) i
    automatyczna wysyłka (Phase 3) zostaną dodane w kolejnych wydaniach.
</div>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Klub</th>
                <th>NIP</th>
                <th>Tryb</th>
                <th>Status</th>
                <th>Ostatni test</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak klubów w bazie.</td></tr>
        <?php else: foreach ($rows as $r):
            $enabled = (int)($r['enabled'] ?? 0) === 1;
            $mode    = (string)($r['mode'] ?? 'test');
            $status  = (string)($r['last_connection_test_status'] ?? 'never');
            $sCls    = match($status) {
                'ok' => 'success', 'failed' => 'danger', default => 'secondary',
            };
            $sLabel  = match($status) {
                'ok' => 'OK', 'failed' => 'Błąd', default => 'Brak',
            };
        ?>
            <tr>
                <td>
                    <strong><?= View::e((string)$r['club_name']) ?></strong>
                    <small class="text-muted d-block">ID: <?= (int)$r['club_id'] ?></small>
                </td>
                <td>
                    <?php if (!empty($r['nip'])): ?>
                        <code><?= View::e((string)$r['nip']) ?></code>
                    <?php else: ?>
                        <span class="text-muted small">brak</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $mode === 'prod' ? 'warning text-dark' : 'info' ?>">
                        <?= strtoupper($mode) ?>
                    </span>
                </td>
                <td>
                    <?php if ($enabled): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Włączony</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Wyłączony</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $sCls ?>"><?= $sLabel ?></span>
                    <?php if (!empty($r['last_connection_test_at'])): ?>
                        <small class="d-block text-muted"><?= format_datetime($r['last_connection_test_at']) ?></small>
                    <?php endif; ?>
                    <?php if (!empty($r['last_connection_test_message'])): ?>
                        <small class="d-block text-muted" title="<?= View::e((string)$r['last_connection_test_message']) ?>">
                            <?= View::e(mb_substr((string)$r['last_connection_test_message'], 0, 60)) ?><?= mb_strlen((string)$r['last_connection_test_message']) > 60 ? '…' : '' ?>
                        </small>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <form method="POST" action="<?= url('admin/platform/ksef/' . (int)$r['club_id'] . '/toggle') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-<?= $enabled ? 'danger' : 'success' ?>">
                            <i class="bi bi-<?= $enabled ? 'toggle-off' : 'toggle-on' ?>"></i>
                            <?= $enabled ? 'Wyłącz' : 'Włącz' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
