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
$statusLabels = [
    'new' => 'Nowe', 'in_progress' => 'W trakcie', 'resolved' => 'Rozwiazane',
    'wont_fix' => 'Nie naprawimy', 'duplicate' => 'Duplikat',
];
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-bug"></i> Zgloszenia bledow i propozycji</h1>
        <a href="<?= url('support/report') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus"></i> Nowe zgloszenie
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <form method="GET" action="<?= url('admin/support') ?>" class="mb-3">
        <div class="d-flex gap-2 align-items-center">
            <label class="form-label mb-0">Status:</label>
            <select name="status" class="form-select form-select-sm" style="max-width: 200px;" onchange="this.form.submit()">
                <option value="">Wszystkie</option>
                <?php foreach (($allowedStatus ?? []) as $s): ?>
                    <option value="<?= View::e($s) ?>" <?= (($statusFilter ?? '') === $s) ? 'selected' : '' ?>>
                        <?= View::e($statusLabels[$s] ?? $s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Typ</th>
                    <th>Tytul</th>
                    <th>Zglaszajacy</th>
                    <th>Status</th>
                    <th>Todoist</th>
                    <th>Data</th>
                    <th>Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak zgloszen.</td></tr>
                <?php else: foreach ($reports as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><span class="badge bg-secondary"><?= View::e($typeLabels[$r['type']] ?? $r['type']) ?></span></td>
                        <td>
                            <strong><?= View::e($r['title']) ?></strong>
                            <?php if (!empty($r['screenshot_path'])): ?>
                                <i class="bi bi-paperclip text-muted" title="zalacznik"></i>
                            <?php endif; ?>
                            <br><small class="text-muted"><?= View::e(mb_substr((string)$r['description'], 0, 120)) ?><?= mb_strlen((string)$r['description']) > 120 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <small>
                                <?= View::e($r['submitter_name'] ?: '(nieznany)') ?><br>
                                <span class="text-muted"><?= View::e($r['submitter_email'] ?: '') ?></span>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-<?= View::e($statusBadge[$r['status']] ?? 'secondary') ?>">
                                <?= View::e($statusLabels[$r['status']] ?? $r['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($r['todoist_task_id'])): ?>
                                <span class="badge bg-success" title="synced">OK</span>
                                <br><small class="text-muted"><?= View::e($r['todoist_task_id']) ?></small>
                            <?php elseif (!empty($r['todoist_sync_error'])): ?>
                                <span class="badge bg-warning text-dark" title="<?= View::e($r['todoist_sync_error']) ?>">err</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= View::e($r['created_at']) ?></small></td>
                        <td>
                            <form method="POST" action="<?= url('admin/support/' . (int)$r['id'] . '/status') ?>" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <?php foreach (($allowedStatus ?? []) as $s): ?>
                                        <option value="<?= View::e($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>>
                                            <?= View::e($statusLabels[$s] ?? $s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" title="zapisz status">
                                    <i class="bi bi-check"></i>
                                </button>
                            </form>
                            <?php if (!empty($r['screenshot_path'])): ?>
                                <a href="<?= url($r['screenshot_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-1">
                                    <i class="bi bi-image"></i> zrzut
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
