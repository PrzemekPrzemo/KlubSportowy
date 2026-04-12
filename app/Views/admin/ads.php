<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Reklamy</h4>
    <a href="<?= url('admin/ads/create') ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nowa reklama</a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Tytul</th>
                <th>Klub</th>
                <th>Cel</th>
                <th>Pozycja</th>
                <th>Status</th>
                <th>Wyswietlenia</th>
                <th>Klikniecia</th>
                <th>Okres</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($ads)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak reklam.</td></tr>
        <?php else: ?>
            <?php foreach ($ads as $a): ?>
                <tr>
                    <td><strong><?= View::e($a['title']) ?></strong></td>
                    <td><?= $a['club_name'] ? View::e($a['club_name']) : '<span class="badge bg-info">globalny</span>' ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($a['target']) ?></span></td>
                    <td><?= View::e($a['position']) ?></td>
                    <td>
                        <span class="badge bg-<?= $a['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $a['is_active'] ? 'aktywna' : 'nieaktywna' ?>
                        </span>
                    </td>
                    <td><?= (int)$a['impressions'] ?></td>
                    <td><?= (int)$a['clicks'] ?></td>
                    <td>
                        <?= $a['start_date'] ? format_date($a['start_date']) : '—' ?>
                        &ndash;
                        <?= $a['end_date'] ? format_date($a['end_date']) : '—' ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('admin/ads/' . (int)$a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="<?= url('admin/ads/' . (int)$a['id'] . '/delete') ?>" class="d-inline"
                              onsubmit="return confirm('Usunac reklame?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
