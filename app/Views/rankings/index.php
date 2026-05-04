<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Rankingi sportowe</h4>
    <?php if ($filterSport): ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#rankingModal">
        <i class="bi bi-plus-circle"></i> Dodaj punkty
    </button>
    <?php endif; ?>
</div>

<!-- Filtry -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <label class="form-label mb-0">Sport</label>
        <select name="sport" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— wybierz sport —</option>
            <?php foreach ($sports as $key => $s): ?>
                <option value="<?= View::e($key) ?>" <?= $filterSport === $key ? 'selected' : '' ?>>
                    <?= View::e($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($filterSport && !empty($seasons)): ?>
    <div class="col-auto">
        <label class="form-label mb-0">Sezon</label>
        <select name="season" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($seasons as $s): ?>
                <option value="<?= View::e($s) ?>" <?= $season === $s ? 'selected' : '' ?>><?= View::e($s) ?></option>
            <?php endforeach; ?>
            <option value="<?= date('Y') ?>" <?= $season === date('Y') ? 'selected' : '' ?>><?= date('Y') ?></option>
        </select>
        <input type="hidden" name="sport" value="<?= View::e($filterSport) ?>">
    </div>
    <?php endif; ?>
</form>

<?php if (!$filterSport): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Wybierz sport, aby zobaczyć ranking.</div>
<?php else: ?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong><?= View::e($sports[$filterSport]['name'] ?? $filterSport) ?> — sezon <?= View::e($season) ?></strong>
    </div>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>#</th><th>Zawodnik</th><th>Nr</th><th>Punkty</th><th>Zawody</th><th>Wygrane</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($rankings)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak danych rankingowych dla tego sezonu.</td></tr>
        <?php else: ?>
            <?php foreach ($rankings as $i => $r): ?>
                <tr <?= $i === 0 ? 'class="table-warning fw-bold"' : '' ?>>
                    <td>
                        <?php if ($r['ranking_position'] === 1): ?>🥇
                        <?php elseif ($r['ranking_position'] === 2): ?>🥈
                        <?php elseif ($r['ranking_position'] === 3): ?>🥉
                        <?php else: ?><?= View::e($r['ranking_position']) ?>.
                        <?php endif; ?>
                    </td>
                    <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                    <td class="text-muted small"><?= View::e($r['member_number']) ?></td>
                    <td><strong><?= View::e($r['ranking_points']) ?></strong></td>
                    <td><?= View::e($r['competitions_count']) ?></td>
                    <td><?= View::e($r['wins']) ?></td>
                    <td>
                        <form method="POST" action="<?= url('sport-rankings/'.(int)$r['id'].'/delete') ?>"
                              onsubmit="return confirm('Usunąć wpis rankingowy?')">
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

<!-- Modal: Dodaj punkty -->
<div class="modal fade" id="rankingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('sport-rankings/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="sport_key" value="<?= View::e($filterSport) ?>">
                <input type="hidden" name="season" value="<?= View::e($season) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bar-chart me-1"></i> Dodaj punkty rankingowe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Punkty rankingowe *</label>
                        <input type="number" name="ranking_points" class="form-control" required min="0" placeholder="np. 100">
                        <div class="form-text">Punkty zostaną dodane do istniejącego konta zawodnika w tym sezonie.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-bar-chart me-1"></i> Dodaj punkty</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
