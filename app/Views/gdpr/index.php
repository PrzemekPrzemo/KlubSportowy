<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="type" class="form-select">
                <option value="">— wszystkie typy —</option>
                <?php foreach ($types as $k => $v): ?>
                    <option value="<?= View::e($k) ?>" <?= ($typeFilter ?? '') === $k ? 'selected' : '' ?>><?= View::e($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtruj</button></div>
    </form>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Zawodnik</th><th>Typ zgody</th><th>Status</th><th>Data udzielenia</th><th>Data cofnięcia</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak zgód.</td></tr>
        <?php else: foreach ($pagination['data'] as $c): ?>
            <tr>
                <td><a href="<?= url('gdpr/member/' . (int)$c['member_id']) ?>"><?= View::e($c['last_name']) ?> <?= View::e($c['first_name']) ?></a></td>
                <td><?= View::e($c['consent_type']) ?></td>
                <td><span class="badge bg-<?= $c['granted'] ? 'success' : 'danger' ?>"><?= $c['granted'] ? 'udzielona' : 'cofnięta' ?></span></td>
                <td><small><?= format_datetime($c['granted_at'] ?? null) ?></small></td>
                <td><small><?= format_datetime($c['revoked_at'] ?? null) ?></small></td>
                <td class="text-end"><a href="<?= url('gdpr/member/' . (int)$c['member_id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
