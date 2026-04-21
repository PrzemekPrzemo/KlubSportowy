<?php use App\Helpers\View; ?>
<?php include ROOT_PATH . '/app/Views/shooting/_shotero_banner.php'; ?>
<?php if (!empty($expiring)): ?>
<div class="alert alert-warning">
    <strong><i class="bi bi-exclamation-triangle"></i> <?= count($expiring) ?> licencji wymaga odnowienia w ciągu 60 dni</strong>
</div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="type" class="form-select">
                <option value="">— wszystkie typy —</option>
                <?php foreach (['zawodnicza','trenerska','sedziowska','klubowa','patent'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($filterType ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtruj</button></div>
        <div class="col-md-3"><a href="<?= url('shooting/licenses/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowa licencja</a></div>
    </form>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Typ</th><th>Numer</th><th>Wydana</th><th>Ważna do</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak licencji.</td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $l): ?>
                <tr>
                    <td><a href="<?= url('members/' . (int)$l['member_id']) ?>"><?= View::e($l['last_name']) ?> <?= View::e($l['first_name']) ?></a></td>
                    <td><span class="badge bg-info"><?= View::e($l['license_type']) ?></span></td>
                    <td><code><?= View::e($l['license_number']) ?></code></td>
                    <td><small><?= format_date($l['issue_date']) ?></small></td>
                    <td><strong><?= format_date($l['valid_until']) ?></strong></td>
                    <td>
                        <span class="badge bg-<?= alert_class((int)$l['days_remaining'], 60) ?>">
                            <?php if ((int)$l['days_remaining'] < 0): ?>
                                wygasła
                            <?php else: ?>
                                <?= (int)$l['days_remaining'] ?> dni
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('shooting/licenses/' . (int)$l['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
