<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('rollerskating/equipment/create') ?>" class="btn btn-success"><i class="bi bi-plus"></i> Nowy sprzęt</a>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Typ</th><th>Marka / Model</th><th>Rozmiar</th><th>Stan</th><th>Przypisany</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak sprzętu.</td></tr>
        <?php else: foreach ($items as $i): ?>
            <tr>
                <td><span class="badge bg-info"><?= View::e($i['type']) ?></span></td>
                <td><?= View::e($i['brand'] ?? '') ?> <?= View::e($i['model'] ?? '') ?></td>
                <td><?= View::e($i['size'] ?? '—') ?></td>
                <td><small><?= View::e($i['condition_state']) ?></small></td>
                <td><?= $i['member_id'] ? View::e($i['last_name'] . ' ' . $i['first_name']) : '<span class="text-muted">klubowy</span>' ?></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('rollerskating/equipment/' . (int)$i['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
