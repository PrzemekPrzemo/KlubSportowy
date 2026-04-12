<?php use App\Helpers\View; ?>
<div class="mb-3 d-flex justify-content-between">
    <div class="text-muted">Kopie przechowywane w <code>storage/backups/</code></div>
    <form method="POST" action="<?= url('admin/backups/create') ?>" class="m-0">
        <?= csrf_field() ?>
        <button class="btn btn-success"><i class="bi bi-plus-circle"></i> Utwórz kopię teraz</button>
    </form>
</div>
<div class="card">
    <table class="table mb-0">
        <thead class="table-light"><tr><th>Plik</th><th>Rozmiar</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($files)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Brak kopii.</td></tr>
        <?php else: foreach ($files as $f): ?>
            <tr>
                <td><code><?= View::e($f['name']) ?></code></td>
                <td><?= number_format($f['size'] / 1024, 1) ?> KB</td>
                <td><small><?= View::e($f['created']) ?></small></td>
                <td class="text-end">
                    <a href="<?= url('admin/backups/' . urlencode($f['name']) . '/download') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                    <form method="POST" action="<?= url('admin/backups/' . urlencode($f['name']) . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
