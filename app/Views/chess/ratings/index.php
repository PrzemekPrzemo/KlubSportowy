<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Rankingi ELO — Szachy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ratingModal">
        <i class="bi bi-plus-circle"></i> Dodaj ocenę
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Zawodnik</th>
                <th>Nr</th>
                <?php foreach ($ratingTypes as $typeKey => $typeLabel): ?>
                    <th class="text-center"><?= View::e($typeLabel) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($byMember)): ?>
            <tr><td colspan="<?= 2 + count($ratingTypes) ?>" class="text-center text-muted py-4">Brak wpisów.</td></tr>
        <?php else: ?>
            <?php foreach ($byMember as $row): ?>
                <tr>
                    <td><strong><?= View::e($row['last_name']) ?> <?= View::e($row['first_name']) ?></strong></td>
                    <td class="text-muted small"><?= View::e($row['member_number']) ?></td>
                    <?php foreach ($ratingTypes as $typeKey => $typeLabel): ?>
                        <td class="text-center">
                            <?php if (isset($row['ratings'][$typeKey])): ?>
                                <strong><?= View::e($row['ratings'][$typeKey]['rating']) ?></strong>
                                <br><span class="text-muted small"><?= View::e($row['ratings'][$typeKey]['rating_date']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Dodaj ocenę ELO -->
<div class="modal fade" id="ratingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('chess/ratings/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bar-chart me-1"></i> Dodaj ocenę ELO</h5>
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
                    <div class="mb-3">
                        <label class="form-label">Typ rankingu *</label>
                        <select name="rating_type" class="form-select" required>
                            <?php foreach ($ratingTypes as $tKey => $tLabel): ?>
                                <option value="<?= $tKey ?>"><?= View::e($tLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ocena ELO *</label>
                            <input type="number" name="rating" class="form-control" required min="100" max="3000" placeholder="np. 1500">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data oceny *</label>
                            <input type="date" name="rating_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <input type="text" name="notes" class="form-control" placeholder="opcjonalne">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-bar-chart me-1"></i> Zapisz ocenę</button>
                </div>
            </form>
        </div>
    </div>
</div>
