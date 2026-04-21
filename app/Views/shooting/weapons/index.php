<?php use App\Helpers\View; ?>
<?php include ROOT_PATH . '/app/Views/shooting/_shotero_banner.php'; ?>
<div class="mb-3 text-end">
    <a href="<?= url('shooting/weapons/create') ?>" class="btn btn-success">
        <i class="bi bi-plus"></i> Nowa broń
    </a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Kategoria</th><th>Marka/Model</th><th>Kaliber</th><th>Nr seryjny</th><th>Stan</th><th>Wypożyczona</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($weapons)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak broni w ewidencji.</td></tr>
        <?php else: ?>
            <?php foreach ($weapons as $w): ?>
                <tr>
                    <td><span class="badge bg-secondary"><?= View::e($w['category']) ?></span></td>
                    <td>
                        <a href="<?= url('shooting/weapons/' . (int)$w['id']) ?>">
                            <?= View::e($w['brand'] ?? '') ?> <?= View::e($w['model'] ?? '') ?>
                        </a>
                    </td>
                    <td><?= View::e($w['caliber'] ?? '') ?></td>
                    <td><code><?= View::e($w['serial_number']) ?></code></td>
                    <td><small><?= View::e($w['condition_state']) ?></small></td>
                    <td>
                        <?php if (!empty($w['current_holder_id'])): ?>
                            <?= View::e($w['holder_last']) ?> <?= View::e($w['holder_first']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('shooting/weapons/' . (int)$w['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
