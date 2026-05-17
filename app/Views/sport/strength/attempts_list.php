<?php
use App\Helpers\View;
/** @var string $sportKey */
/** @var array $manifest */
/** @var array $attempts */
/** @var array $members */
/** @var ?int $memberId */
/** @var ?string $liftType */
/** @var array $liftTypes */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-shield-shaded text-warning me-2"></i>
        Podejścia — <?= View::e($manifest['name'] ?? $sportKey) ?>
    </h3>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="member_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— wybierz zawodnika —</option>
            <?php foreach ($members as $m): ?>
                <option value="<?= (int)$m['id'] ?>" <?= $memberId === (int)$m['id'] ? 'selected' : '' ?>>
                    <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="lift_type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— wszystkie typy —</option>
            <?php foreach ($liftTypes as $val => $lbl): ?>
                <option value="<?= View::e($val) ?>" <?= $liftType === $val ? 'selected' : '' ?>>
                    <?= View::e($lbl) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">Nowe podejście</div>
            <div class="card-body">
                <form method="POST" action="<?= url('club/sport/' . $sportKey . '/attempt/store') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Zawodnik *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">—</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= $memberId === (int)$m['id'] ? 'selected' : '' ?>>
                                    <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Typ *</label>
                        <select name="lift_type" class="form-select" required>
                            <?php foreach ($liftTypes as $val => $lbl): ?>
                                <option value="<?= View::e($val) ?>"><?= View::e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label">Nr podejścia</label>
                            <input type="number" name="attempt_number" value="1" min="1" max="9" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Waga [kg]</label>
                            <input type="number" step="0.5" name="weight_kg" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Powt.</label>
                            <input type="number" name="reps" value="1" min="1" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2 form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="success" id="successChk" value="1">
                        <label class="form-check-label" for="successChk">Zaliczone</label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Turniej (opcjonalnie)</label>
                        <input type="number" name="tournament_id" class="form-control" placeholder="ID turnieju">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2-circle"></i> Zapisz
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                Historia podejść <?= $memberId ? '(wybrany zawodnik)' : '(wybierz zawodnika)' ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Typ</th>
                            <th>Nr</th>
                            <th>Waga</th>
                            <th>Powt.</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($attempts as $a): ?>
                        <tr>
                            <td><?= View::e($a['attempted_at']) ?></td>
                            <td><?= View::e($liftTypes[$a['lift_type']] ?? $a['lift_type']) ?></td>
                            <td><?= (int)$a['attempt_number'] ?></td>
                            <td><?= $a['weight_kg'] !== null ? number_format((float)$a['weight_kg'], 1) . ' kg' : '—' ?></td>
                            <td><?= (int)$a['reps'] ?></td>
                            <td>
                                <?php if ((int)$a['success'] === 1): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">FAIL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="<?= url('club/sport/' . $sportKey . '/attempt/' . (int)$a['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Usunąć podejście?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$attempts): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">Wybierz zawodnika aby wyświetlić podejścia.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
