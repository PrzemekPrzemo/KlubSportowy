<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-activity me-2"></i>Wyniki zawodów — Trójbój siłowy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
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
        <table class="table table-hover mb-0" style="min-width:900px;">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Zawody</th>
                    <th>Data</th>
                    <th>Kat. wagowa</th>
                    <th>Przysiad</th>
                    <th>Wyciskanie</th>
                    <th>Martwy</th>
                    <th>Total</th>
                    <th>Wilks</th>
                    <th>Miejsce</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r):
                    $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                ?>
                    <tr>
                        <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                        <td>
                            <?= View::e($r['competition_name']) ?>
                            <br><span class="badge bg-secondary"><?= View::e($federations[$r['federation_type']] ?? $r['federation_type']) ?></span>
                        </td>
                        <td><?= View::e($r['competition_date']) ?></td>
                        <td><?= View::e($r['weight_class']) ?> kg</td>
                        <td><?= $r['squat_best']    !== null ? number_format((float)$r['squat_best'],    1).' kg' : '—' ?></td>
                        <td><?= $r['bench_best']    !== null ? number_format((float)$r['bench_best'],    1).' kg' : '—' ?></td>
                        <td><?= $r['deadlift_best'] !== null ? number_format((float)$r['deadlift_best'], 1).' kg' : '—' ?></td>
                        <td class="fw-semibold"><?= $r['total'] !== null ? number_format((float)$r['total'], 1).' kg' : '—' ?></td>
                        <td><?= $r['wilks_coeff'] !== null ? number_format((float)$r['wilks_coeff'], 2) : '—' ?></td>
                        <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                        <td>
                            <form method="POST" action="<?= url('powerlifting/results/'.(int)$r['id'].'/delete') ?>"
                                  onsubmit="return confirm('Usunąć wynik?')">
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

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('powerlifting/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-activity me-1"></i> Dodaj wynik zawodów</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="row g-3">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <label class="form-label">Nazwa zawodów *</label>
                            <input type="text" name="competition_name" class="form-control" required
                                   placeholder="np. Mistrzostwa Polski Seniorów">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Data *</label>
                            <input type="date" name="competition_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Federacja</label>
                            <select name="federation_type" class="form-select">
                                <?php foreach ($federations as $key => $label): ?>
                                    <option value="<?= View::e($key) ?>"
                                        <?= $key === 'PZTSS' ? 'selected' : '' ?>>
                                        <?= View::e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control"
                                   placeholder="np. Junior, Senior, Masters">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Kategoria wagowa *</label>
                            <div class="input-group">
                                <input type="text" name="weight_class" class="form-control" required
                                       id="wc_input" placeholder="np. -83">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                        data-bs-toggle="dropdown"></button>
                                <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:220px;">
                                    <li><h6 class="dropdown-header">Mężczyźni</h6></li>
                                    <?php foreach ($weightClassesMen as $wc): ?>
                                        <li><a class="dropdown-item wc-pick" href="#" data-val="<?= $wc ?>"><?= $wc ?> kg</a></li>
                                    <?php endforeach; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Kobiety</h6></li>
                                    <?php foreach ($weightClassesWomen as $wc): ?>
                                        <li><a class="dropdown-item wc-pick" href="#" data-val="<?= $wc ?>"><?= $wc ?> kg</a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Masa ciała (kg)</label>
                            <input type="number" name="body_weight" class="form-control"
                                   step="0.01" min="0" placeholder="np. 82.50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Płeć (do Wilks)</label>
                            <select name="sex" class="form-select">
                                <option value="M">Mężczyzna</option>
                                <option value="F">Kobieta</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-3">
                    <p class="fw-semibold mb-2">Wyniki podniesień (najlepsze próby)</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Przysiad (kg)</label>
                            <input type="number" name="squat_best" class="form-control lift-input"
                                   step="0.5" min="0" placeholder="np. 200.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Wyciskanie (kg)</label>
                            <input type="number" name="bench_best" class="form-control lift-input"
                                   step="0.5" min="0" placeholder="np. 130.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Martwy ciąg (kg)</label>
                            <input type="number" name="deadlift_best" class="form-control lift-input"
                                   step="0.5" min="0" placeholder="np. 250.0">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Total (kg)</label>
                            <input type="text" id="total_display" class="form-control bg-light" readonly
                                   placeholder="obliczany automatycznie">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Punkty IPF GL</label>
                            <input type="number" name="ipf_gl_points" class="form-control"
                                   step="0.001" min="0" placeholder="opcjonalnie">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="np. 1">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-activity me-1"></i> Zapisz wynik
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-compute total display
document.querySelectorAll('.lift-input').forEach(function(el) {
    el.addEventListener('input', function() {
        var squat    = parseFloat(document.querySelector('[name=squat_best]').value)    || 0;
        var bench    = parseFloat(document.querySelector('[name=bench_best]').value)    || 0;
        var deadlift = parseFloat(document.querySelector('[name=deadlift_best]').value) || 0;
        if (squat > 0 && bench > 0 && deadlift > 0) {
            document.getElementById('total_display').value = (squat + bench + deadlift).toFixed(1) + ' kg';
        } else {
            document.getElementById('total_display').value = '';
        }
    });
});
// Weight class quick-pick
document.querySelectorAll('.wc-pick').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('wc_input').value = this.dataset.val;
    });
});
</script>
