<?php use App\Helpers\View; use App\Sports\Cycling\Models\CyclingResultModel; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki zawodów — Kolarstwo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Zawody</th><th>Data</th><th>Typ</th><th>Dystans</th><th>Kat. UCI</th><th>Czas</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($results as $r):
                $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
            ?>
                <tr>
                    <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                    <td><?= View::e($r['competition_name']) ?></td>
                    <td><?= View::e($r['competition_date']) ?></td>
                    <td><?= $r['race_type'] ? View::e($raceTypes[$r['race_type']] ?? $r['race_type']) : '—' ?></td>
                    <td><?= $r['distance_km'] ? View::e($r['distance_km']).' km' : '—' ?></td>
                    <td><?= $r['uci_category'] ? View::e($r['uci_category']) : '—' ?></td>
                    <td><code><?= View::e(CyclingResultModel::formatTime(isset($r['time_seconds']) && $r['time_seconds'] !== null ? (float)$r['time_seconds'] : null)) ?></code></td>
                    <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('cycling/results/'.(int)$r['id'].'/delete') ?>"
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
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('cycling/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy me-1"></i> Dodaj wynik zawodów</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nazwa zawodów *</label>
                        <input type="text" name="competition_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data *</label>
                            <input type="date" name="competition_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control" placeholder="np. U18, Senior">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Typ wyścigu</label>
                            <select name="race_type" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($raceTypes as $key => $label): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dystans (km)</label>
                            <input type="number" name="distance_km" class="form-control" min="0" step="0.1" placeholder="np. 120.5">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria UCI</label>
                            <select name="uci_category" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($uciCategories as $cat): ?>
                                    <option value="<?= View::e($cat) ?>"><?= View::e($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Czas (s)</label>
                            <input type="number" name="time_seconds" class="form-control" min="0" step="0.01" placeholder="np. 3661.50">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria</label>
                            <select name="category" class="form-select">
                                <option value="">— ogólna —</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1">
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
