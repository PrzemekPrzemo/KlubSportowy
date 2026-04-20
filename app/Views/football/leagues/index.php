<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('football/leagues/create') ?>" class="btn btn-success"><i class="bi bi-plus"></i> Nowa liga</a>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nazwa</th>
                <th>Sezon</th>
                <th>Od</th>
                <th>Do</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($leagues)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Brak lig.</td></tr>
        <?php else: foreach ($leagues as $l): ?>
            <tr>
                <td><a href="<?= url('football/leagues/' . (int)$l['id']) ?>"><strong><?= View::e($l['name']) ?></strong></a></td>
                <td><?= View::e($l['season']) ?></td>
                <td><?= $l['start_date'] ? View::e($l['start_date']) : '—' ?></td>
                <td><?= $l['end_date'] ? View::e($l['end_date']) : '—' ?></td>
                <td class="text-end">
                    <a href="<?= url('football/leagues/' . (int)$l['id']) ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-table"></i> Tabela</a>
                    <form method="POST" action="<?= url('football/leagues/' . (int)$l['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć ligę?')" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
