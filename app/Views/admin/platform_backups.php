<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">Backupy klubow — platforma</h4>
        <div class="text-muted small">Lista WSZYSTKICH backupow we wszystkich klubach. Tylko super admin.</div>
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="POST" action="<?= url('admin/platform/backups/force') ?>" class="row g-2 align-items-end m-0">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <label class="form-label">Klub</label>
            <select name="club_id" class="form-select" required>
                <option value="">-- wybierz klub --</option>
                <?php foreach (($clubs ?? []) as $c): ?>
                    <option value="<?= (int)$c['id'] ?>">#<?= (int)$c['id'] ?> — <?= View::e((string)$c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-warning">
                <i class="bi bi-shield-exclamation"></i> Wymus backup
            </button>
        </div>
    </form>
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Klub</th>
                <th>Typ</th>
                <th>Status</th>
                <th>Rozmiar</th>
                <th>Utworzono</th>
                <th>Wygasa</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($backups)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak backupow.</td></tr>
        <?php else: foreach ($backups as $b): ?>
            <tr>
                <td><code>#<?= (int)$b['id'] ?></code></td>
                <td><?= View::e((string)($b['club_name'] ?? '?')) ?> <span class="text-muted small">(<?= (int)$b['club_id'] ?>)</span></td>
                <td><span class="badge bg-secondary"><?= View::e((string)$b['type']) ?></span></td>
                <td>
                    <?php $s = (string)$b['status']; ?>
                    <?php if ($s === 'completed'): ?><span class="badge bg-success">ok</span>
                    <?php elseif ($s === 'in_progress'): ?><span class="badge bg-warning text-dark">w trakcie</span>
                    <?php else: ?><span class="badge bg-danger">blad</span><?php endif; ?>
                </td>
                <td><?= $b['backup_size_bytes'] ? number_format(((int)$b['backup_size_bytes']) / 1024, 1) . ' KB' : '—' ?></td>
                <td class="small"><?= View::e((string)($b['started_at'] ?? '')) ?></td>
                <td class="small"><?= View::e((string)($b['expires_at']  ?? '')) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
