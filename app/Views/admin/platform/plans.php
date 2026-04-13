<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('admin/platform/plans/create') ?>" class="btn btn-success"><i class="bi bi-plus"></i> NOWY PLAN</a>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Kod</th><th>Nazwa</th><th>Miesięcznie</th><th>Rocznie</th><th>Max członków</th><th>Max sportów</th><th>Aktywny</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($plans as $p): ?>
            <tr>
                <td><code><?= View::e($p['code']) ?></code></td>
                <td><strong><?= View::e($p['name']) ?></strong></td>
                <td><?= format_money($p['price_monthly']) ?></td>
                <td><?= format_money($p['price_yearly']) ?></td>
                <td><?= $p['max_members'] !== null ? (int)$p['max_members'] : '∞' ?></td>
                <td><?= $p['max_sports'] !== null ? (int)$p['max_sports'] : '∞' ?></td>
                <td><span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>"><?= $p['is_active'] ? 'tak' : 'nie' ?></span></td>
                <td class="text-end">
                    <a href="<?= url('admin/platform/plans/' . (int)$p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
