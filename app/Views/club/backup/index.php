<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">Kopie zapasowe klubu</h4>
        <div class="text-muted small">Pelny eksport (czlonkowie + platnosci + treningi + media + integracje) jako ZIP. Domyslnie usuwane po 30 dniach.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('club/backup/restore') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-upload"></i> Przywroc z backupu
        </a>
        <form method="POST" action="<?= url('club/backup/create') ?>" class="m-0">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="sync">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Utworz backup
            </button>
        </form>
    </div>
</div>

<div class="alert alert-info small">
    <strong>GDPR portability:</strong> dane sa Twoje. Backup mozesz uzyc do przeniesienia
    klubu na inna instalacje ClubDesk lub do archiwum poza systemem. Sekrety integracji
    (klucze bramek, hasla SMTP) NIE sa eksportowane — wpisz je ponownie po restore.
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Typ</th>
                <th>Status</th>
                <th>Rozmiar</th>
                <th>Wiersze</th>
                <th>Pliki</th>
                <th>Utworzono</th>
                <th>Wygasa</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($backups)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak backupow. Utworz pierwszy.</td></tr>
        <?php else: foreach ($backups as $b): ?>
            <tr>
                <td><code>#<?= (int)$b['id'] ?></code></td>
                <td><span class="badge bg-secondary"><?= View::e($b['type']) ?></span></td>
                <td>
                    <?php $s = (string)$b['status']; ?>
                    <?php if ($s === 'completed'): ?>
                        <span class="badge bg-success">gotowy</span>
                    <?php elseif ($s === 'in_progress'): ?>
                        <span class="badge bg-warning text-dark">w trakcie</span>
                    <?php else: ?>
                        <span class="badge bg-danger">blad</span>
                        <?php if (!empty($b['error_message'])): ?>
                            <div class="small text-muted" title="<?= View::e((string)$b['error_message']) ?>">
                                <?= View::e(mb_substr((string)$b['error_message'], 0, 60)) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td><?= $b['backup_size_bytes'] ? number_format(((int)$b['backup_size_bytes']) / 1024, 1) . ' KB' : '—' ?></td>
                <td><?= $b['rows_exported']  !== null ? (int)$b['rows_exported']  : '—' ?></td>
                <td><?= $b['files_exported'] !== null ? (int)$b['files_exported'] : '—' ?></td>
                <td class="small"><?= View::e((string)($b['started_at'] ?? '')) ?></td>
                <td class="small"><?= View::e((string)($b['expires_at']  ?? '')) ?></td>
                <td class="text-end">
                    <?php if ($s === 'completed'): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('club/backup/' . (int)$b['id'] . '/download') ?>">
                            <i class="bi bi-download"></i> Pobierz
                        </a>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('club/backup/' . (int)$b['id'] . '/delete') ?>" class="d-inline"
                          onsubmit="return confirm('Usunac backup #<?= (int)$b['id'] ?>?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
