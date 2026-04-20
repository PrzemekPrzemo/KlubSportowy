<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-star me-2"></i>Rekordy klubu — Trójbój siłowy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recordModal">
        <i class="bi bi-plus-circle"></i> Dodaj rekord
    </button>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Typ</th>
                    <th>Kat. wagowa</th>
                    <th>Wynik (kg)</th>
                    <th>Zawodnik</th>
                    <th>Data</th>
                    <th>Zawody</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak rekordów.</td></tr>
            <?php else: ?>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?= View::e($liftTypes[$r['lift_type']] ?? $r['lift_type']) ?></span></td>
                        <td><?= View::e($r['weight_class'] ? $r['weight_class'].' kg' : '—') ?></td>
                        <td class="fw-bold"><?= number_format((float)$r['weight_kg'], 1) ?> kg</td>
                        <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                        <td><?= View::e($r['set_date']) ?></td>
                        <td><?= View::e($r['competition'] ?? '—') ?></td>
                        <td>
                            <form method="POST" action="<?= url('powerlifting/records/'.(int)$r['id'].'/delete') ?>"
                                  onsubmit="return confirm('Usunąć rekord?')">
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
</div>

<!-- Modal: Dodaj rekord -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('powerlifting/records/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-star me-1"></i> Dodaj rekord klubu</h5>
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
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Typ podnoszenia *</label>
                            <select name="lift_type" class="form-select" required>
                                <?php foreach ($liftTypes as $key => $label): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wagowa</label>
                            <select name="weight_class" class="form-select">
                                <option value="">— open —</option>
                                <optgroup label="Mężczyźni">
                                    <?php foreach ($weightClassesMen as $wc): ?>
                                        <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Kobiety">
                                    <?php foreach ($weightClassesWomen as $wc): ?>
                                        <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Wynik (kg) *</label>
                            <input type="number" name="weight_kg" class="form-control"
                                   step="0.5" min="0" required placeholder="np. 250.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data *</label>
                            <input type="date" name="set_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Zawody</label>
                        <input type="text" name="competition" class="form-control"
                               placeholder="np. Mistrzostwa Polski">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-star me-1"></i> Zapisz rekord
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
