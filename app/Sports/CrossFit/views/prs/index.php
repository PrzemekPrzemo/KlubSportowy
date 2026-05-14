<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Personal Records — CrossFit</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#prModal"
            <?= $selectedMember ? '' : 'disabled title="Wybierz zawodnika"' ?>>
        <i class="bi bi-plus-circle"></i> Dodaj PR
    </button>
</div>

<!-- Member selector -->
<div class="card mb-3 p-3">
    <form method="GET" action="<?= url('crossfit/prs') ?>" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-semibold">Zawodnik</label>
            <select name="member_id" class="form-select" onchange="this.form.submit()">
                <option value="">— wybierz zawodnika —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= $selectedMember === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if (!$selectedMember): ?>
    <div class="alert alert-info"><i class="bi bi-person-circle me-2"></i>Wybierz zawodnika, aby zobaczyć jego rekordy.</div>
<?php elseif (empty($prs)): ?>
    <div class="alert alert-secondary">Brak rekordów dla wybranego zawodnika.</div>
<?php else: ?>

<!-- Top PRs summary -->
<?php if (!empty($topPrs)): ?>
<div class="row g-2 mb-3">
    <?php
    $unitLabels = ['kg'=>'kg','lb'=>'lb','reps'=>'reps','time'=>'time','m'=>'m','cal'=>'cal'];
    foreach ($topPrs as $pr): ?>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card text-center p-2 border-primary">
            <div class="small text-muted"><?= View::e($pr['movement']) ?></div>
            <div class="fs-5 fw-bold text-primary"><?= View::e($pr['pr_value']) ?> <small><?= $unitLabels[$pr['unit']] ?? $pr['unit'] ?></small></div>
            <div class="small text-muted"><?= View::e($pr['pr_date']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Full PR history table -->
<div class="card">
    <div class="card-header fw-semibold">Historia rekordów</div>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Ćwiczenie</th><th>Wynik</th><th>Jednostka</th><th>Data</th><th>Notatki</th><th></th></tr>
        </thead>
        <tbody>
        <?php
        $currentMovement = null;
        foreach ($prs as $pr):
            $isNew = $pr['movement'] !== $currentMovement;
            $currentMovement = $pr['movement'];
        ?>
            <tr class="<?= $isNew ? 'table-primary' : '' ?>">
                <td>
                    <?php if ($isNew): ?>
                        <strong><?= View::e($pr['movement']) ?></strong>
                        <span class="badge bg-primary ms-1" style="font-size:.65rem">PR</span>
                    <?php else: ?>
                        <span class="text-muted ps-2">↳</span>
                    <?php endif; ?>
                </td>
                <td class="fw-bold"><?= View::e($pr['pr_value']) ?></td>
                <td><span class="badge bg-secondary"><?= View::e($pr['unit']) ?></span></td>
                <td><?= View::e($pr['pr_date']) ?></td>
                <td class="text-muted small"><?= View::e($pr['notes'] ?? '') ?></td>
                <td>
                    <form method="POST" action="<?= url('crossfit/prs/' . (int)$pr['id'] . '/delete') ?>"
                          onsubmit="return confirm('Usunąć ten rekord?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Modal: Dodaj PR -->
<div class="modal fade" id="prModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('crossfit/prs/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="member_id" value="<?= (int)$selectedMember ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bar-chart-line me-1"></i> Nowy Personal Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ćwiczenie *</label>
                        <input type="text" name="movement" class="form-control" required
                               list="movementList" placeholder="np. Back Squat, Deadlift, Clean...">
                        <datalist id="movementList">
                            <?php foreach ($commonMovements as $mv): ?>
                                <option value="<?= View::e($mv) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Wynik / wartość *</label>
                            <input type="text" name="pr_value" class="form-control" required
                                   placeholder="np. 120, 25, 3:45">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Jednostka *</label>
                            <select name="unit" class="form-select" required>
                                <option value="kg">kg</option>
                                <option value="lb">lb</option>
                                <option value="reps">reps</option>
                                <option value="time">time</option>
                                <option value="m">m</option>
                                <option value="cal">cal</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data *</label>
                        <input type="date" name="pr_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Zapisz PR</button>
                </div>
            </form>
        </div>
    </div>
</div>
