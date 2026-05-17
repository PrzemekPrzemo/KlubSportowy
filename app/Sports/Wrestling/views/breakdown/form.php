<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="bi bi-list-check text-primary me-2"></i>
        Protokol techniczny meczu #<?= (int)$matchId ?>
    </h4>
    <a href="<?= url('wrestling/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wroc
    </a>
</div>

<form method="POST" action="<?= url('wrestling/breakdown/' . (int)$matchId . '/store') ?>" class="card shadow-sm mb-4">
    <?= csrf_field() ?>
    <div class="card-header"><i class="bi bi-plus-circle me-1"></i> Dodaj wpis</div>
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">Zawodnik</label>
            <select name="member_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>">
                        <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?> (#<?= View::e($m['member_number']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><label class="form-label">Takedowns</label>
            <input type="number" min="0" class="form-control" name="takedowns" value="0">
        </div>
        <div class="col-md-2"><label class="form-label">Exposures</label>
            <input type="number" min="0" class="form-control" name="exposures" value="0">
        </div>
        <div class="col-md-2"><label class="form-label">Escapes</label>
            <input type="number" min="0" class="form-control" name="escapes" value="0">
        </div>
        <div class="col-md-2"><label class="form-label">Cautions</label>
            <input type="number" min="0" class="form-control" name="caution_count" value="0">
        </div>
        <div class="col-md-3">
            <label class="form-label d-block">&nbsp;</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="tf" name="technical_fall" value="1">
                <label class="form-check-label" for="tf">Technical fall</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="pin" name="pin" value="1">
                <label class="form-check-label" for="pin">Pin</label>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-success"><i class="bi bi-save me-1"></i> Zapisz</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-table me-1"></i> Istniejace wpisy</div>
    <div class="card-body p-0">
        <?php if (empty($existing)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wpisow.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Zawodnik</th><th>TD</th><th>EXP</th><th>ESC</th><th>TF</th><th>Pin</th><th>Cautions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($existing as $b): ?>
                        <tr>
                            <td><strong><?= View::e(($b['last_name'] ?? '') . ' ' . ($b['first_name'] ?? '')) ?></strong></td>
                            <td><?= (int)$b['takedowns'] ?></td>
                            <td><?= (int)$b['exposures'] ?></td>
                            <td><?= (int)$b['escapes'] ?></td>
                            <td><?= !empty($b['technical_fall']) ? '<span class="badge bg-warning text-dark">TF</span>' : '—' ?></td>
                            <td><?= !empty($b['pin']) ? '<span class="badge bg-danger">PIN</span>' : '—' ?></td>
                            <td><?= (int)$b['caution_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
