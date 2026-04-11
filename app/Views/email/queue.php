<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">— wszystkie —</option>
                <?php foreach (['pending','sending','sent','failed'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100">Filtruj</button></div>
    </form>
</div>

<div class="card">
    <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr><th>Utworzono</th><th>Do</th><th>Temat</th><th>Typ</th><th>Status</th><th>Wysłano</th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Kolejka pusta.</td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $e): ?>
                <tr>
                    <td><small><?= format_datetime($e['created_at']) ?></small></td>
                    <td><small><?= View::e($e['to_email']) ?></small></td>
                    <td><small><?= View::e(substr($e['subject'], 0, 50)) ?></small></td>
                    <td><small><code><?= View::e($e['template_type'] ?? '—') ?></code></small></td>
                    <td>
                        <?php $cls = match($e['status']) {
                            'sent' => 'success',
                            'failed' => 'danger',
                            'sending' => 'info',
                            default => 'secondary'
                        }; ?>
                        <span class="badge bg-<?= $cls ?>"><?= View::e($e['status']) ?></span>
                    </td>
                    <td><small><?= $e['sent_at'] ? format_datetime($e['sent_at']) : '—' ?></small></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
