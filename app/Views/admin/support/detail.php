<?php
use App\Helpers\View;

$statusBadge = [
    'new' => 'primary', 'in_progress' => 'info', 'resolved' => 'success',
    'wont_fix' => 'secondary', 'duplicate' => 'secondary',
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

$r = $report ?? [];
$id = (int)($r['id'] ?? 0);
$st = (string)($r['status'] ?? 'new');
$tp = (string)($r['type'] ?? 'other');

// Timeline construction
$timeline = [];
if (!empty($r['created_at'])) {
    $timeline[] = ['ts' => $r['created_at'], 'label' => 'Zgloszenie utworzone', 'icon' => 'plus-circle', 'cls' => 'primary'];
}
if (!empty($r['todoist_task_id']) && !empty($r['todoist_synced_at'])) {
    $timeline[] = ['ts' => $r['todoist_synced_at'], 'label' => 'Zsynchronizowano z Todoist', 'icon' => 'cloud-arrow-up', 'cls' => 'info'];
}
if (!empty($r['resolved_at'])) {
    $label = 'Zamkniete (' . ($statusLabels[$st] ?? $st) . ')';
    if (!empty($resolverName)) $label .= ' przez ' . $resolverName;
    $timeline[] = ['ts' => $r['resolved_at'], 'label' => $label, 'icon' => 'check2-circle', 'cls' => 'success'];
}
usort($timeline, fn($a, $b) => strcmp($a['ts'], $b['ts']));
?>
<div class="container py-4" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <a href="<?= url('admin/support') ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Lista
            </a>
            <span class="h4 mb-0">Zgloszenie #<?= $id ?></span>
            <span class="badge bg-<?= View::e($typeBadge[$tp] ?? 'secondary') ?> ms-2">
                <?= View::e($typeLabels[$tp] ?? $tp) ?>
            </span>
            <span class="badge bg-<?= View::e($statusBadge[$st] ?? 'secondary') ?>">
                <?= View::e($statusLabels[$st] ?? $st) ?>
            </span>
        </div>
        <?php if (!empty($r['todoist_task_id'])): ?>
            <a href="https://app.todoist.com/app/task/<?= View::e($r['todoist_task_id']) ?>"
               target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-up-right"></i> Otworz w Todoist
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5"><?= View::e($r['title'] ?? '') ?></h2>
                    <div class="text-muted small mb-3">
                        Utworzono: <?= View::e($r['created_at'] ?? '') ?>
                    </div>
                    <div class="border rounded p-3 bg-light">
                        <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= View::e($r['description'] ?? '') ?></pre>
                    </div>

                    <?php if (!empty($r['url_context'])): ?>
                        <div class="mt-3">
                            <small class="text-muted d-block">URL strony skad zgloszono:</small>
                            <code class="small"><?= View::e($r['url_context']) ?></code>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($r['user_agent'])): ?>
                        <div class="mt-2">
                            <small class="text-muted d-block">User agent:</small>
                            <code class="small"><?= View::e($r['user_agent']) ?></code>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($r['screenshot_path'])): ?>
                        <div class="mt-3">
                            <small class="text-muted d-block mb-1">Zrzut ekranu:</small>
                            <a href="<?= url($r['screenshot_path']) ?>" target="_blank">
                                <img src="<?= url($r['screenshot_path']) ?>" alt="screenshot"
                                     class="img-fluid border rounded" style="max-height: 480px;">
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($r['resolution_notes'])): ?>
                        <div class="alert alert-info mt-3">
                            <strong>Notatka rozwiazania:</strong><br>
                            <?= nl2br(View::e($r['resolution_notes'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status change form -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-pencil-square"></i> Zmien status
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('admin/support/' . $id . '/status') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="return" value="/admin/support/<?= $id ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small">Nowy status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach (($allowedStatus ?? []) as $s): ?>
                                        <option value="<?= View::e($s) ?>" <?= $st === $s ? 'selected' : '' ?>>
                                            <?= View::e($statusLabels[$s] ?? $s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small">Notatka (gdy rozwiazane/duplicate/wont_fix)</label>
                                <input type="text" name="resolution_notes" class="form-control form-control-sm"
                                       value="<?= View::e($r['resolution_notes'] ?? '') ?>" maxlength="2000"
                                       placeholder="Opcjonalna notatka">
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm mt-3">
                            <i class="bi bi-check"></i> Zapisz
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person"></i> Zglaszajacy</div>
                <div class="card-body py-2">
                    <p class="mb-1"><strong><?= View::e($r['submitter_name'] ?: '(nieznany)') ?></strong></p>
                    <?php if (!empty($r['submitter_email'])): ?>
                        <p class="mb-1 small"><a href="mailto:<?= View::e($r['submitter_email']) ?>"><?= View::e($r['submitter_email']) ?></a></p>
                    <?php endif; ?>
                    <p class="mb-0 small text-muted">
                        <?php if (!empty($r['user_id'])): ?>user_id: <?= (int)$r['user_id'] ?><?php endif; ?>
                        <?php if (!empty($r['member_id'])): ?> | member_id: <?= (int)$r['member_id'] ?><?php endif; ?>
                        <?php if (!empty($r['club_id'])): ?> | club_id: <?= (int)$r['club_id'] ?><?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-clock-history"></i> Oś czasu</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($timeline as $ev): ?>
                        <li class="list-group-item d-flex gap-2 py-2">
                            <i class="bi bi-<?= View::e($ev['icon']) ?> text-<?= View::e($ev['cls']) ?>"></i>
                            <div class="flex-grow-1">
                                <div><?= View::e($ev['label']) ?></div>
                                <small class="text-muted"><?= View::e($ev['ts']) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-cloud"></i> Todoist sync</div>
                <div class="card-body py-2">
                    <?php if (!empty($r['todoist_task_id'])): ?>
                        <p class="mb-1">
                            <strong>Task ID:</strong>
                            <a href="https://app.todoist.com/app/task/<?= View::e($r['todoist_task_id']) ?>"
                               target="_blank" rel="noopener">
                                <?= View::e($r['todoist_task_id']) ?>
                            </a>
                        </p>
                    <?php else: ?>
                        <p class="mb-1 text-muted">Brak zsynchronizowanego zadania.</p>
                    <?php endif; ?>
                    <p class="mb-1 small">
                        Ostatni sync: <?= View::e($r['todoist_synced_at'] ?: '-') ?>
                    </p>
                    <?php if (!empty($r['todoist_sync_error'])): ?>
                        <div class="alert alert-warning small mt-2 mb-0 py-1 px-2">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?= View::e($r['todoist_sync_error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
