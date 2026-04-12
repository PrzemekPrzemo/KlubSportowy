<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tokeny demo</h4>
    <div class="d-flex gap-2">
        <form method="POST" action="<?= url('admin/demos/create') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-success"><i class="bi bi-plus-circle"></i> Nowe demo</button>
        </form>
        <form method="POST" action="<?= url('admin/demos/cleanup') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-outline-warning"><i class="bi bi-trash"></i> Wyczysc wygasle</button>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Klub</th>
                <th>Token</th>
                <th>Link demo</th>
                <th>Wygasa</th>
                <th>Utworzony przez</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tokens)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak aktywnych tokenow demo.</td></tr>
        <?php else: ?>
            <?php foreach ($tokens as $t): ?>
                <tr>
                    <td><strong><?= View::e($t['club_name']) ?></strong></td>
                    <td><code class="small"><?= View::e(substr($t['token'], 0, 16)) ?>...</code></td>
                    <td>
                        <a href="<?= url('demo/' . View::e($t['token'])) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> Otworz
                        </a>
                    </td>
                    <td><?= format_datetime($t['expires_at']) ?></td>
                    <td><?= View::e($t['creator_name'] ?? '—') ?></td>
                    <td><?= format_datetime($t['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
