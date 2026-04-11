<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('shooting/judges/create') ?>" class="btn btn-success"><i class="bi bi-plus"></i> Nowa licencja sędziowska</a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Sędzia</th><th>Klasa</th><th>Numer</th><th>Dyscypliny</th><th>Ważna do</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($judges)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak sędziów.</td></tr>
        <?php else: ?>
            <?php foreach ($judges as $j): ?>
                <tr>
                    <td><a href="<?= url('members/' . (int)$j['member_id']) ?>"><?= View::e($j['last_name']) ?> <?= View::e($j['first_name']) ?></a></td>
                    <td><span class="badge bg-primary">klasa <?= View::e($j['class']) ?></span></td>
                    <td><code><?= View::e($j['license_number']) ?></code></td>
                    <td><small><?= View::e($j['disciplines'] ?? '—') ?></small></td>
                    <td><strong><?= format_date($j['valid_until']) ?></strong></td>
                    <td>
                        <span class="badge bg-<?= alert_class((int)$j['days_remaining'], 60) ?>">
                            <?php if ((int)$j['days_remaining'] < 0): ?>wygasła<?php else: ?><?= (int)$j['days_remaining'] ?> dni<?php endif; ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('shooting/judges/' . (int)$j['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
