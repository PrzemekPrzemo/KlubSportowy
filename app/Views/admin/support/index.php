<?php
use App\Helpers\View;

$statusBadge = [
    'new'         => 'primary',
    'in_progress' => 'info',
    'resolved'    => 'success',
    'wont_fix'    => 'secondary',
    'duplicate'   => 'secondary',
];
$typeLabels = [
    'bug' => 'Blad', 'feature' => 'Propozycja', 'question' => 'Pytanie', 'other' => 'Inne',
];
$typeBadge = [
    'bug' => 'danger', 'feature' => 'success', 'question' => 'info', 'other' => 'secondary',
];
$statusLabels = [
    'new' => 'Nowe', 'in_progress' => 'W trakcie', 'resolved' => 'Rozwiazane',
    'wont_fix' => 'Nie naprawimy', 'duplicate' => 'Duplikat',
];

$stats = $stats ?? ['by_status' => [], 'by_type' => [], 'resolved_last_30d' => 0, 'avg_resolution_h' => null];

/**
 * Relative time helper (PL).
 */
$relTime = static function (?string $dt): string {
    if (!$dt) return '-';
    $ts = strtotime($dt);
    if ($ts === false) return (string)$dt;
    $diff = time() - $ts;
    if ($diff < 60)     return $diff . 's temu';
    if ($diff < 3600)   return floor($diff / 60) . ' min temu';
    if ($diff < 86400)  return floor($diff / 3600) . ' h temu';
    if ($diff < 604800) return floor($diff / 86400) . ' dni temu';
    return date('Y-m-d', $ts);
};

$maxByType = max(1, ...array_map('intval', array_values($stats['by_type'] ?? [1])));
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 mb-0"><i class="bi bi-bug"></i> Zgloszenia bledow i propozycji</h1>
        <div class="d-flex gap-2">
            <form method="POST" action="<?= url('admin/support/sync-now') ?>" class="m-0"
                  onsubmit="return confirm('Wymusic synchronizacje statusow zgloszen z Todoistem? Moze potrwac do 30s.');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Wymus sync statusow zgloszen z Todoistem">
                    <i class="bi bi-arrow-repeat"></i> Synchronizuj z Todoist
                </button>
            </form>
            <a href="<?= url('support/report') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus"></i> Nowe zgloszenie
            </a>
        </div>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <!-- Stats panel -->
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card border-primary">
                <div class="card-body py-2">
                    <small class="text-muted">Nowe</small>
                    <h3 class="mb-0 text-primary"><?= (int)($stats['by_status']['new'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-info">
                <div class="card-body py-2">
                    <small class="text-muted">W trakcie</small>
                    <h3 class="mb-0 text-info"><?= (int)($stats['by_status']['in_progress'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-success">
                <div class="card-body py-2">
                    <small class="text-muted">Rozwiazane (30d)</small>
                    <h3 class="mb-0 text-success"><?= (int)($stats['resolved_last_30d'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-secondary">
                <div class="card-body py-2">
                    <small class="text-muted">Sredni czas rozwiazania</small>
                    <h3 class="mb-0">
                        <?php if ($stats['avg_resolution_h'] === null): ?>
                            <span class="text-muted">-</span>
                        <?php else:
                            $h = (float)$stats['avg_resolution_h'];
                            if ($h < 24) {
                                echo number_format($h, 1) . ' h';
                            } else {
                                echo number_format($h / 24, 1) . ' d';
                            }
                        ?>
                        <?php endif; ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Per-type breakdown -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <small class="text-muted d-block mb-2">Wedlug typu zgloszenia</small>
            <?php foreach ($stats['by_type'] as $tKey => $tCount):
                $pct = $maxByType > 0 ? min(100, (int)round(($tCount / $maxByType) * 100)) : 0;
                $cls = $typeBadge[$tKey] ?? 'secondary';
            ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div style="width: 100px;"><small><?= View::e($typeLabels[$tKey] ?? $tKey) ?></small></div>
                    <div class="flex-grow-1 bg-light" style="height: 16px; border-radius: 4px; overflow: hidden;">
                        <div class="bg-<?= View::e($cls) ?>" style="width: <?= $pct ?>%; height: 100%;"></div>
                    </div>
                    <div style="width: 50px;" class="text-end"><small class="text-muted"><?= (int)$tCount ?></small></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filtry -->
    <form method="GET" action="<?= url('admin/support') ?>" class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <?php foreach (($allowedStatus ?? []) as $s): ?>
                            <option value="<?= View::e($s) ?>" <?= (($statusFilter ?? '') === $s) ? 'selected' : '' ?>>
                                <?= View::e($statusLabels[$s] ?? $s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Typ</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <?php foreach (($allowedTypes ?? []) as $t): ?>
                            <option value="<?= View::e($t) ?>" <?= (($typeFilter ?? '') === $t) ? 'selected' : '' ?>>
                                <?= View::e($typeLabels[$t] ?? $t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Okres</label>
                    <select name="period" class="form-select form-select-sm">
                        <option value="all" <?= (($period ?? 'all') === 'all') ? 'selected' : '' ?>>Wszystkie</option>
                        <option value="7"   <?= (($period ?? '') === '7')   ? 'selected' : '' ?>>Ostatnie 7 dni</option>
                        <option value="30"  <?= (($period ?? '') === '30')  ? 'selected' : '' ?>>Ostatnie 30 dni</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Szukaj w tytule</label>
                    <input type="search" name="q" class="form-control form-control-sm"
                           value="<?= View::e($searchQ ?? '') ?>" placeholder="np. logowanie">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel"></i> Filtruj
                    </button>
                    <a href="<?= url('admin/support') ?>" class="btn btn-outline-secondary btn-sm" title="Wyczysc">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Utworzono</th>
                    <th>Zglaszajacy</th>
                    <th>Typ</th>
                    <th>Tytul</th>
                    <th>Status</th>
                    <th>Todoist</th>
                    <th>Ost. sync</th>
                    <th class="text-end">Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Brak zgloszen.</td></tr>
                <?php else: foreach ($reports as $r):
                    $tBadge = $typeBadge[$r['type']] ?? 'secondary';
                    $sBadge = $statusBadge[$r['status']] ?? 'secondary';
                    $isClosed = in_array($r['status'], ['resolved', 'wont_fix', 'duplicate'], true);
                ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><small><?= View::e($relTime($r['created_at'] ?? null)) ?></small></td>
                        <td>
                            <small>
                                <?= View::e($r['submitter_name'] ?: '(nieznany)') ?>
                                <?php if (!empty($r['submitter_email'])): ?>
                                    <br><span class="text-muted"><?= View::e($r['submitter_email']) ?></span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-<?= View::e($tBadge) ?>">
                                <?= View::e($typeLabels[$r['type']] ?? $r['type']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= url('admin/support/' . (int)$r['id']) ?>" class="text-decoration-none">
                                <strong><?= View::e($r['title']) ?></strong>
                            </a>
                            <?php if (!empty($r['screenshot_path'])): ?>
                                <i class="bi bi-paperclip text-muted" title="zalacznik"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= View::e($sBadge) ?>">
                                <?= View::e($statusLabels[$r['status']] ?? $r['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($r['todoist_task_id'])): ?>
                                <a href="https://app.todoist.com/app/task/<?= View::e($r['todoist_task_id']) ?>"
                                   target="_blank" rel="noopener" class="text-decoration-none"
                                   title="Otworz w Todoist (<?= View::e($r['todoist_task_id']) ?>)">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <small>Todoist</small>
                                </a>
                                <?php if (!empty($r['todoist_sync_error'])): ?>
                                    <i class="bi bi-exclamation-triangle-fill text-warning ms-1"
                                       title="<?= View::e($r['todoist_sync_error']) ?>"></i>
                                <?php endif; ?>
                            <?php elseif (!empty($r['todoist_sync_error'])): ?>
                                <i class="bi bi-exclamation-triangle-fill text-warning"
                                   title="<?= View::e($r['todoist_sync_error']) ?>"></i>
                                <small class="text-warning">err</small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= View::e($relTime($r['todoist_synced_at'] ?? null)) ?></small></td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end flex-wrap">
                                <a href="<?= url('admin/support/' . (int)$r['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Szczegoly">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (!$isClosed): ?>
                                    <form method="POST" action="<?= url('admin/support/' . (int)$r['id'] . '/status') ?>"
                                          class="d-inline" onsubmit="return confirm('Oznaczyc jako rozwiazane?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="resolved">
                                        <button class="btn btn-sm btn-outline-success" title="Quick resolve">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= url('admin/support/' . (int)$r['id'] . '/status') ?>"
                                          class="d-inline" onsubmit="return confirm('Otworzyc ponownie?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="new">
                                        <button class="btn btn-sm btn-outline-warning" title="Reopen">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
