<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('football/teams/create') ?>" class="btn btn-success"><i class="bi bi-plus"></i> Nowa drużyna</a>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Nazwa</th><th>Liga</th><th>Kategoria</th><th>Trener</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($teams)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Brak drużyn.</td></tr>
        <?php else: foreach ($teams as $t): ?>
            <tr>
                <td><strong><?= View::e($t['name']) ?></strong></td>
                <td><?= View::e($t['league'] ?? '—') ?></td>
                <td><?= View::e($t['age_cat_name'] ?? '—') ?></td>
                <td><?= View::e($t['coach_name'] ?? '—') ?></td>
                <td class="text-end">
                    <a href="<?= url('football/teams/' . (int)$t['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
