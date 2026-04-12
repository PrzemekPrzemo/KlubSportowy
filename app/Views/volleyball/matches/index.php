<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">— wszystkie —</option>
                <?php foreach (['zaplanowany','w_trakcie','zakonczony','odwolany'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
        <div class="col-md-3"><a href="<?= url('volleyball/matches/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowy mecz</a></div>
    </form>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Data</th><th>Mecz</th><th>Typ</th><th>Sety</th><th>Punkty</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak meczów.</td></tr>
        <?php else: foreach ($pagination['data'] as $m): ?>
            <tr>
                <td><small><?= format_datetime($m['match_date']) ?></small></td>
                <td>
                    <a href="<?= url('volleyball/matches/' . (int)$m['id']) ?>">
                        <strong><?= View::e($m['home_team_name']) ?></strong> vs <?= View::e($m['away_team']) ?>
                    </a>
                </td>
                <td><span class="badge bg-info"><?= View::e($m['match_type']) ?></span></td>
                <td>
                    <?php if ($m['home_sets'] !== null): ?>
                        <strong><?= (int)$m['home_sets'] ?> : <?= (int)$m['away_sets'] ?></strong>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if ($m['home_score'] !== null): ?>
                        <?= (int)$m['home_score'] ?> : <?= (int)$m['away_score'] ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><small><?= View::e($m['status']) ?></small></td>
                <td class="text-end">
                    <a href="<?= url('volleyball/matches/' . (int)$m['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
