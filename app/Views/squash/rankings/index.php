<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Ranking PSA — Squash</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#rankingModal">
        <i class="bi bi-plus-circle"></i> Dodaj / aktualizuj
    </button>
</div>

<!-- Season filter -->
<form method="GET" class="mb-3 d-flex align-items-center gap-2">
    <label class="form-label mb-0">Sezon:</label>
    <select name="season" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        <option value="">— wszystkie —</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= View::e($s) ?>" <?= $season === $s ? 'selected' : '' ?>>
                <?= View::e($s) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>#</th><th>Zawodnik</th><th>Nr</th><th>Sezon</th><th>Rating PSA</th><th>Poz. PSA</th><th>Zaktualizowano</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($rankings)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak wpisów rankingowych.</td></tr>
        <?php else: ?>
            <?php foreach ($rankings as $i => $r): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                    <td class="text-muted small"><?= View::e($r['member_number']) ?></td>
                    <td><?= View::e($r['season']) ?></td>
                    <td><strong><?= View::e($r['psa_rating']) ?></strong></td>
                    <td><?= $r['psa_position'] ? View::e($r['psa_position']) : '—' ?></td>
                    <td class="text-muted small"><?= View::e(substr($r['updated_at'], 0, 10)) ?></td>
                    <td>
                        <form method="POST" action="<?= url('squash/rankings/'.(int)$r['id'].'/delete') ?>"
                              onsubmit="return confirm('Usunąć wpis?')">
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

<!-- Modal: Dodaj / aktualizuj ranking -->
<div class="modal fade" id="rankingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('squash/rankings/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bar-chart me-1"></i> Dodaj / aktualizuj ranking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                                    (#<?= View::e($m['member_number']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sezon *</label>
                            <input type="text" name="season" class="form-control" required
                                   placeholder="np. 2024/2025" value="<?= View::e($season) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rating PSA *</label>
                            <input type="number" name="psa_rating" class="form-control" required min="0" placeholder="np. 1500">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-bar-chart me-1"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
