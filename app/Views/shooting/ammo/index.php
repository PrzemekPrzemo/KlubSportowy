<?php use App\Helpers\View; ?>
<?php include ROOT_PATH . '/app/Views/shooting/_shotero_banner.php'; ?>
<div class="mb-3 text-end">
    <a href="<?= url('shooting/ammo/create') ?>" class="btn btn-success">
        <i class="bi bi-plus"></i> Nowa pozycja
    </a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Kaliber</th><th>Typ</th><th>Marka</th><th class="text-end">Stan</th><th>Min.</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($stock)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Magazyn pusty.</td></tr>
        <?php else: ?>
            <?php foreach ($stock as $a): ?>
                <tr class="<?= !empty($a['low_stock']) ? 'table-warning' : '' ?>">
                    <td><a href="<?= url('shooting/ammo/' . (int)$a['id']) ?>"><strong><?= View::e($a['caliber']) ?></strong></a></td>
                    <td><?= View::e($a['type'] ?? '') ?></td>
                    <td><?= View::e($a['brand'] ?? '') ?></td>
                    <td class="text-end"><strong><?= (int)$a['quantity'] ?></strong> szt.</td>
                    <td><?= $a['min_stock'] !== null ? (int)$a['min_stock'] : '—' ?></td>
                    <td class="text-end">
                        <?php if (!empty($a['low_stock'])): ?>
                            <span class="badge bg-danger">niski stan</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
