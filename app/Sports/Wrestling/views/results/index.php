<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Wyniki zawodów — Zapasy</h4>
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
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Zawody</th>
                    <th>Data</th>
                    <th>Styl</th>
                    <th>Kat. wagowa</th>
                    <th>Kat. wiek.</th>
                    <th>Miejsce</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r):
                    $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                    $styleBadgeColor = match($r['style']) {
                        'freestyle'   => 'bg-primary',
                        'greco_roman' => 'bg-danger',
                        'women'       => 'bg-success',
                        default       => 'bg-secondary',
                    };
                ?>
                    <tr>
                        <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                        <td><?= View::e($r['competition_name']) ?></td>
                        <td><?= View::e($r['competition_date']) ?></td>
                        <td><span class="badge <?= $styleBadgeColor ?>"><?= View::e($styles[$r['style']] ?? $r['style']) ?></span></td>
                        <td><?= $r['weight_class'] ? View::e($r['weight_class']).' kg' : '—' ?></td>
                        <td><?= View::e($r['age_category'] ?? '—') ?></td>
                        <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('wrestling/results/'.(int)$r['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Szczegóły">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('wrestling/results/'.(int)$r['id'].'/edit') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?= url('wrestling/results/'.(int)$r['id'].'/delete') ?>"
                                      onsubmit="return confirm('Usunąć wynik?')" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" title="Usuń">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
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
            <form method="POST" action="<?= url('wrestling/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people-fill me-1"></i> Dodaj wynik zawodów</h5>
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
                            <label class="form-label">Styl *</label>
                            <select name="style" class="form-select" id="styleSelect" required>
                                <?php foreach ($styles as $key => $label): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategoria wagowa</label>
                            <select name="weight_class" class="form-select" id="weightClassSelect">
                                <option value="">— open —</option>
                                <optgroup label="Freestyle — Mężczyźni" id="wc_freestyle_men">
                                    <?php foreach ($weightClassesMen as $wc): ?>
                                        <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Klasyczny — Mężczyźni" id="wc_greco" style="display:none;">
                                    <?php foreach ($weightClassesGreco as $wc): ?>
                                        <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Kobiety (Freestyle)" id="wc_women" style="display:none;">
                                    <?php foreach ($weightClassesWomen as $wc): ?>
                                        <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control"
                                   placeholder="np. U17, Junior, Senior, Masters">
                        </div>
                        <div class="col-md-6">
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
                        <i class="bi bi-people-fill me-1"></i> Zapisz wynik
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle weight class optgroups based on selected style
document.getElementById('styleSelect').addEventListener('change', function() {
    var style = this.value;
    var grFreestyleMen = document.getElementById('wc_freestyle_men');
    var grGreco        = document.getElementById('wc_greco');
    var grWomen        = document.getElementById('wc_women');
    var sel            = document.getElementById('weightClassSelect');

    // Reset
    grFreestyleMen.style.display = 'none';
    grGreco.style.display        = 'none';
    grWomen.style.display        = 'none';
    sel.value = '';

    if (style === 'freestyle') {
        grFreestyleMen.style.display = '';
    } else if (style === 'greco_roman') {
        grGreco.style.display = '';
    } else if (style === 'women') {
        grWomen.style.display = '';
    }
});
// Init on page load
document.getElementById('styleSelect').dispatchEvent(new Event('change'));
</script>
