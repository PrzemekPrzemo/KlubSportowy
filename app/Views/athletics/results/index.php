<?php use App\Helpers\View;
use App\Sports\Athletics\Models\AthleticsResultModel; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki zawodów — Lekka atletyka</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<!-- Filtr dyscypliny -->
<form method="GET" class="d-flex gap-2 align-items-center mb-3">
    <select name="discipline" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        <option value="">— wszystkie dyscypliny —</option>
        <?php foreach ($disciplines as $d): ?>
            <option value="<?= View::e($d) ?>" <?= $discFilter === $d ? 'selected' : '' ?>><?= View::e($d) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card">
    <table class="table table-hover table-sm mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Dyscyplina</th><th>Wynik</th><th>Wiatr</th><th>Zawody</th><th>Data</th><th>Kat.</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $r):
                $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
            ?>
            <tr>
                <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                <td><?= View::e($r['discipline_name']) ?></td>
                <td class="fw-bold font-monospace"><?= View::e(AthleticsResultModel::formatResult((float)$r['result_value'], $r['result_unit'])) ?></td>
                <td class="text-muted small"><?= $r['wind_ms'] !== null ? View::e($r['wind_ms']).' m/s' : '—' ?></td>
                <td><?= View::e($r['competition_name']) ?></td>
                <td><?= View::e($r['competition_date']) ?></td>
                <td class="text-muted small"><?= View::e($r['age_category'] ?? '—') ?></td>
                <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                <td>
                    <form method="POST" action="<?= url('athletics/results/'.(int)$r['id'].'/delete') ?>"
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

<?php if ($pagination['last_page'] > 1): ?>
<nav class="mt-2">
    <ul class="pagination pagination-sm justify-content-end mb-0">
        <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
            <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?><?= $discFilter ? '&discipline='.urlencode($discFilter) : '' ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('athletics/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy me-1"></i> Dodaj wynik zawodów</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Zawodnik *</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dyscyplina *</label>
                            <input type="text" name="discipline_name" class="form-control" required
                                   list="disciplineList" placeholder="np. 100m, skok wzwyż">
                            <datalist id="disciplineList">
                                <?php foreach ($commonDisc as $group => $items): ?>
                                    <?php foreach ($items as $d): ?>
                                        <option value="<?= View::e($d) ?>">
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Wynik * <small class="text-muted">(liczba)</small></label>
                            <input type="number" name="result_value" class="form-control" required
                                   step="0.001" min="0" placeholder="np. 10.85">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jednostka</label>
                            <select name="result_unit" class="form-select">
                                <?php foreach ($units as $k => $label): ?>
                                    <option value="<?= $k ?>" <?= $k === 's' ? 'selected' : '' ?>><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Wiatr (m/s)</label>
                            <input type="number" name="wind_ms" class="form-control"
                                   step="0.1" placeholder="np. +1.5 lub -0.3">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nazwa zawodów *</label>
                            <input type="text" name="competition_name" class="form-control" required
                                   placeholder="np. Mistrzostwa Polski Juniorów">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data *</label>
                            <input type="date" name="competition_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="1">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <select name="age_category" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($ageCategories as $ac): ?>
                                    <option value="<?= View::e($ac['name']) ?>"><?= View::e($ac['name']) ?> (<?= $ac['age_from'] ?>–<?= $ac['age_to'] ?> lat)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lokalizacja</label>
                            <input type="text" name="location" class="form-control" placeholder="np. Warszawa, Stadion Narodowy">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-trophy me-1"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>
