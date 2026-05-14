<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Wyniki zawodów — Podnoszenie ciężarów</h4>
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
        <table class="table table-hover mb-0" style="min-width:800px;">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Zawody</th>
                    <th>Data</th>
                    <th>Kat.</th>
                    <th>Rwanie</th>
                    <th>Podrzut</th>
                    <th>Total</th>
                    <th>Sinclair</th>
                    <th>Miejsce</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r):
                    $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                ?>
                    <tr>
                        <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                        <td><?= View::e($r['competition_name']) ?></td>
                        <td><?= View::e($r['competition_date']) ?></td>
                        <td><?= View::e($r['weight_class']) ?> kg</td>
                        <td><?= $r['snatch_best']    !== null ? number_format((float)$r['snatch_best'],    1).' kg' : '—' ?></td>
                        <td><?= $r['cleanjerk_best'] !== null ? number_format((float)$r['cleanjerk_best'], 1).' kg' : '—' ?></td>
                        <td class="fw-semibold"><?= $r['total'] !== null ? number_format((float)$r['total'], 1).' kg' : '—' ?></td>
                        <td><?= $r['sinclair_coeff'] !== null ? number_format((float)$r['sinclair_coeff'], 2) : '—' ?></td>
                        <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                        <td>
                            <form method="POST" action="<?= url('weightlifting/results/'.(int)$r['id'].'/delete') ?>"
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
            <form method="POST" action="<?= url('weightlifting/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bar-chart-steps me-1"></i> Dodaj wynik zawodów</h5>
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
                                   placeholder="np. Mistrzostwa Polski Juniorów">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Data *</label>
                            <input type="date" name="competition_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategoria wagowa *</label>
                            <select name="weight_class" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <optgroup label="Mężczyźni (IWF)">
                                    <?php foreach ($weightClassesMen as $wc): ?>
                                        <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Kobiety (IWF)">
                                    <?php foreach ($weightClassesWomen as $wc): ?>
                                        <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Płeć (do Sinclair)</label>
                            <select name="sex" class="form-select">
                                <option value="M">Mężczyzna</option>
                                <option value="F">Kobieta</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Masa ciała (kg)</label>
                            <input type="number" name="body_weight" class="form-control"
                                   step="0.01" min="0" placeholder="np. 72.80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control"
                                   placeholder="np. Junior, Senior, U17">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="np. 1">
                        </div>
                    </div>

                    <hr class="my-3">
                    <p class="fw-semibold mb-2">Wyniki podniesień (najlepsze próby)</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Rwanie — Snatch (kg)</label>
                            <input type="number" name="snatch_best" id="snatch_inp" class="form-control"
                                   step="0.5" min="0" placeholder="np. 130.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Podrzut — C&amp;J (kg)</label>
                            <input type="number" name="cleanjerk_best" id="cj_inp" class="form-control"
                                   step="0.5" min="0" placeholder="np. 165.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total (kg)</label>
                            <input type="text" id="total_display" class="form-control bg-light" readonly
                                   placeholder="obliczany automatycznie">
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
                        <i class="bi bi-bar-chart-steps me-1"></i> Zapisz wynik
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
['snatch_inp','cj_inp'].forEach(function(id) {
    document.getElementById(id).addEventListener('input', function() {
        var snatch = parseFloat(document.getElementById('snatch_inp').value) || 0;
        var cj     = parseFloat(document.getElementById('cj_inp').value)     || 0;
        if (snatch > 0 && cj > 0) {
            document.getElementById('total_display').value = (snatch + cj).toFixed(1) + ' kg';
        } else {
            document.getElementById('total_display').value = '';
        }
    });
});
</script>
