<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-bell text-primary me-2"></i>
        Powiadomienia
    </h3>
    <a href="<?= url('fees/dues') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-clock-history"></i> Należności
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<!-- Dashboard widget: today's stats -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Dziś w kolejce</small>
            <div class="fs-3 fw-bold text-info"><?= (int)$stats['queued'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Dziś wysłane</small>
            <div class="fs-3 fw-bold text-success"><?= (int)$stats['sent'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 <?= $stats['failed'] > 0 ? 'border-danger' : '' ?>">
            <small class="text-muted">Dziś nieudane</small>
            <div class="fs-3 fw-bold text-danger"><?= (int)$stats['failed'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Dziś wyciszone (opt-out)</small>
            <div class="fs-3 fw-bold text-warning"><?= (int)$stats['suppressed'] ?></div>
        </div>
    </div>
</div>

<!-- Reguły -->
<div class="card mb-3">
    <div class="card-header bg-light">
        <strong><i class="bi bi-gear me-1"></i> Reguły wysyłki</strong>
        <small class="text-muted ms-2">Kiedy słać przypomnienia (cron uruchamia codziennie)</small>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Template</th>
                    <th>Wyzwalacz</th>
                    <th>Dni</th>
                    <th>Kanał</th>
                    <th>Max/target</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">
                        Brak reguł. Dodaj pierwszą poniżej.
                    </td></tr>
                <?php else: foreach ($rules as $r): ?>
                    <tr class="<?= empty($r['is_active']) ? 'text-muted' : '' ?>">
                        <td><code><?= View::e($r['template_type']) ?></code></td>
                        <td><?= View::e($triggerEvents[$r['trigger_event']] ?? $r['trigger_event']) ?></td>
                        <td><span class="badge bg-info"><?= (int)$r['days_offset'] ?> dni</span></td>
                        <td><?= View::e($channels[$r['channel']] ?? $r['channel']) ?></td>
                        <td class="text-center"><?= (int)$r['max_per_target'] ?>×</td>
                        <td>
                            <?php if (!empty($r['is_active'])): ?>
                                <span class="badge bg-success">aktywna</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">nieaktywna</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <form method="POST" action="<?= url('club/notifications/rules/' . (int)$r['id'] . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-<?= !empty($r['is_active']) ? 'warning' : 'success' ?>"
                                            title="<?= !empty($r['is_active']) ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                        <i class="bi bi-<?= !empty($r['is_active']) ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?= url('club/notifications/rules/' . (int)$r['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Usunąć regułę?')" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Dodaj nową regułę -->
<div class="card mb-3">
    <div class="card-header bg-light">
        <strong><i class="bi bi-plus-circle me-1"></i> Nowa reguła</strong>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('club/notifications/rules/store') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Template *</label>
                    <select name="template_type" class="form-select" required>
                        <?php foreach ($availableTemplates as $tpl): ?>
                            <option value="<?= View::e($tpl) ?>"><?= View::e($tpl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Wyzwalacz *</label>
                    <select name="trigger_event" class="form-select" required>
                        <?php foreach ($triggerEvents as $key => $label): ?>
                            <option value="<?= $key ?>"><?= View::e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dni *</label>
                    <input type="number" name="days_offset" class="form-control" value="7" min="-365" max="365" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kanał</label>
                    <select name="channel" class="form-select">
                        <?php foreach ($channels as $key => $label): ?>
                            <option value="<?= $key ?>"><?= View::e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max ×</label>
                    <input type="number" name="max_per_target" class="form-control" value="1" min="1" max="20">
                </div>
                <div class="col-12">
                    <label class="form-label">Notatki (opcjonalne)</label>
                    <input type="text" name="notes" class="form-control" maxlength="200"
                           placeholder="np. Pierwszy reminder po terminie">
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2 me-1"></i> Dodaj regułę
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Audit log -->
<div class="card">
    <div class="card-header bg-light">
        <strong><i class="bi bi-list-ul me-1"></i> Ostatnie powiadomienia</strong>
        <small class="text-muted ms-2">50 ostatnich wpisów</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Zawodnik</th>
                    <th>Template</th>
                    <th>Kanał</th>
                    <th>Adresat</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($log)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Brak wpisów. Cron uruchamia się codziennie.</td></tr>
                <?php else: foreach ($log as $l): ?>
                    <tr>
                        <td class="small font-monospace"><?= View::e($l['created_at']) ?></td>
                        <td>
                            <?php if (!empty($l['member_id'])): ?>
                                <?= View::e(($l['last_name'] ?? '') . ' ' . ($l['first_name'] ?? '')) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><code class="small"><?= View::e($l['template_type']) ?></code></td>
                        <td><?= View::e($l['channel']) ?></td>
                        <td class="small text-muted"><?= View::e($l['recipient']) ?></td>
                        <td>
                            <?php
                                $cls = match($l['status']) {
                                    'sent'       => 'success',
                                    'queued'     => 'info',
                                    'failed'     => 'danger',
                                    'suppressed' => 'warning',
                                    default      => 'secondary',
                                };
                            ?>
                            <span class="badge bg-<?= $cls ?>"><?= View::e($l['status']) ?></span>
                            <?php if (!empty($l['error'])): ?>
                                <small class="d-block text-muted" title="<?= View::e($l['error']) ?>">
                                    <?= View::e(mb_strimwidth($l['error'], 0, 50, '…')) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
