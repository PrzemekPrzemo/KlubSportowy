<?php use App\Helpers\View; ?>
<?php if (!empty($expiring)): ?>
<div class="alert alert-warning">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga — <?= count($expiring) ?> badań wymaga odnowienia lub już wygasło:</strong>
    <div class="mt-2 small">
        <?php foreach ($expiring as $e): ?>
            <div>
                <?= View::e($e['last_name']) ?> <?= View::e($e['first_name']) ?>
                — ważne do <strong><?= format_date($e['valid_until']) ?></strong>
                <span class="badge bg-<?= alert_class((int)$e['days_remaining']) ?>">
                    <?= (int)$e['days_remaining'] >= 0 ? '+' : '' ?><?= (int)$e['days_remaining'] ?> dni
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="mb-3 text-end">
    <a href="<?= url('medical/create') ?>" class="btn btn-success">
        <i class="bi bi-plus"></i> Nowe badanie
    </a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data badania</th><th>Zawodnik</th><th>Typ</th><th>Lekarz</th><th>Ważne do</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak badań.</td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $e): ?>
                <tr>
                    <td><?= format_date($e['exam_date']) ?></td>
                    <td>
                        <a href="<?= url('members/' . (int)$e['member_id']) ?>">
                            <?= View::e($e['last_name']) ?> <?= View::e($e['first_name']) ?>
                        </a>
                    </td>
                    <td><small><?= View::e($e['exam_type']) ?></small></td>
                    <td><small><?= View::e($e['doctor_name'] ?? '') ?></small></td>
                    <td><strong><?= format_date($e['valid_until']) ?></strong></td>
                    <td>
                        <?php $cls = alert_class((int)$e['days_remaining']); ?>
                        <span class="badge bg-<?= $cls ?>">
                            <?php if ((int)$e['days_remaining'] < 0): ?>
                                wygasło
                            <?php elseif ((int)$e['days_remaining'] <= 30): ?>
                                za <?= (int)$e['days_remaining'] ?> dni
                            <?php else: ?>
                                ważne
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('medical/' . (int)$e['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
