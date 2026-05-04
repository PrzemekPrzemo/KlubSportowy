<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki — Gimnastyka</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<!-- Filtry dyscypliny -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?= url('gymnastics/results') ?>"
       class="btn btn-sm <?= empty($filterDisc) ? 'btn-primary' : 'btn-outline-primary' ?>">Wszystkie</a>
    <?php foreach ($disciplines as $d): ?>
        <a href="<?= url('gymnastics/results?discipline=' . urlencode($d)) ?>"
           class="btn btn-sm <?= $filterDisc === $d ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= ucfirst($d) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Zawodnik</th><th>Zawody</th><th>Data</th><th>Dyscyplina</th>
                <th>Przyrząd</th><th class="text-center">D</th><th class="text-center">E</th>
                <th class="text-center">P</th><th class="text-center fw-bold">Total</th>
                <th class="text-center">Miejsce</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="11" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                <td><?= View::e($r['event_name']) ?></td>
                <td><?= View::e($r['event_date']) ?></td>
                <td><span class="badge bg-info text-dark"><?= ucfirst(View::e($r['discipline'])) ?></span></td>
                <td><?= View::e($r['apparatus'] ?? '—') ?></td>
                <td class="text-center"><?= number_format((float)$r['difficulty_score'], 3) ?></td>
                <td class="text-center"><?= number_format((float)$r['execution_score'], 3) ?></td>
                <td class="text-center text-danger">-<?= number_format((float)$r['penalty_score'], 3) ?></td>
                <td class="text-center fw-bold"><?= number_format((float)$r['total_score'], 3) ?></td>
                <td class="text-center"><?= $r['placement'] ? '#' . (int)$r['placement'] : '—' ?></td>
                <td>
                    <form method="POST" action="<?= url('gymnastics/results/' . (int)$r['id'] . '/delete') ?>"
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

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('gymnastics/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bar-chart me-1"></i> Dodaj wynik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zawody</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dyscyplina</label>
                            <select name="discipline" class="form-select">
                                <?php foreach ($disciplines as $d): ?>
                                    <option value="<?= $d ?>"><?= ucfirst($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Przyrząd</label>
                            <input type="text" name="apparatus" class="form-control" placeholder="np. Skocznia">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">D-Score (trudność)</label>
                            <input type="number" name="difficulty_score" class="form-control" step="0.001" min="0" value="0.000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">E-Score (wykonanie)</label>
                            <input type="number" name="execution_score" class="form-control" step="0.001" min="0" value="0.000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kary (odjąć)</label>
                            <input type="number" name="penalty_score" class="form-control" step="0.001" min="0" value="0.000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="np. 1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control" placeholder="np. Junior">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>
