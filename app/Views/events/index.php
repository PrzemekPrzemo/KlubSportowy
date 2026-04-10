<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="sport" class="form-select">
                <option value="">— wszystkie sporty —</option>
                <?php foreach ($sports as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (int)($sportFilter ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= View::e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="type" class="form-select">
                <option value="">— typ —</option>
                <?php foreach (['mecz','zawody','trening','turniej','obóz','inny'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($typeFilter ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtruj</button></div>
        <div class="col-md-2"><a href="<?= url('events/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i></a></div>
    </form>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Nazwa</th><th>Typ</th><th>Sport</th><th>Miejsce</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak wydarzeń.</td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $e): ?>
                <tr>
                    <td><small><?= format_datetime($e['event_date']) ?></small></td>
                    <td><strong><?= View::e($e['name']) ?></strong></td>
                    <td><span class="badge bg-info"><?= View::e($e['type']) ?></span></td>
                    <td>
                        <?php if (!empty($e['sport_name'])): ?>
                            <span class="sport-badge" style="background: <?= View::e($e['sport_color']) ?>">
                                <?= View::e($e['sport_name']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= View::e($e['location'] ?? '') ?></td>
                    <td><small class="text-muted"><?= View::e($e['status']) ?></small></td>
                    <td class="text-end">
                        <form method="POST" action="<?= url('events/' . (int)$e['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć?')">
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
