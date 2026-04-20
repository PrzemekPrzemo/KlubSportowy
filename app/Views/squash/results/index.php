<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki meczy — Squash</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Data</th>
                    <th>Zawody</th>
                    <th>Rywal</th>
                    <th>Kat.</th>
                    <th>Sety (W:L)</th>
                    <th>Gry</th>
                    <th>Runda</th>
                    <th>Ranking &Delta;</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r):
                    $delta = $r['ranking_delta'];
                    $deltaHtml = '—';
                    if ($delta !== null) {
                        if ((int)$delta > 0) {
                            $deltaHtml = '<span class="text-success">+'.View::e($delta).'</span>';
                        } elseif ((int)$delta < 0) {
                            $deltaHtml = '<span class="text-danger">'.View::e($delta).'</span>';
                        } else {
                            $deltaHtml = '<span class="text-muted">0</span>';
                        }
                    }
                    $catLabel = $categories[$r['category']] ?? $r['category'];
                ?>
                    <tr>
                        <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                        <td><?= View::e($r['match_date']) ?></td>
                        <td><?= View::e($r['competition_name'] ?? '—') ?></td>
                        <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                        <td><span class="badge bg-secondary"><?= View::e($catLabel) ?></span></td>
                        <td>
                            <?php if ($r['sets_won'] !== null || $r['sets_lost'] !== null): ?>
                                <?= View::e($r['sets_won'] ?? 0) ?>:<?= View::e($r['sets_lost'] ?? 0) ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= View::e($r['games_detail'] ?? '—') ?></td>
                        <td><?= View::e($r['competition_round'] ?? '—') ?></td>
                        <td><?= $deltaHtml ?></td>
                        <td>
                            <form method="POST" action="<?= url('squash/results/'.(int)$r['id'].'/delete') ?>"
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
            <form method="POST" action="<?= url('squash/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-circle me-1"></i> Dodaj wynik meczu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
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
                            <label class="form-label">Data meczu *</label>
                            <input type="date" name="match_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nazwa zawodów</label>
                            <input type="text" name="competition_name" class="form-control" placeholder="np. Polish Open Squash">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategoria</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $cKey => $cLabel): ?>
                                    <option value="<?= $cKey ?>"><?= View::e($cLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Rywal</label>
                            <input type="text" name="opponent_name" class="form-control" placeholder="Imię i nazwisko">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sety wygrane</label>
                            <input type="number" name="sets_won" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sety przegrane</label>
                            <input type="number" name="sets_lost" class="form-control" min="0" placeholder="0">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Szczegóły gier</label>
                            <input type="text" name="games_detail" class="form-control" placeholder="np. 11-5, 11-8, 11-9">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Runda</label>
                            <input type="text" name="competition_round" class="form-control" placeholder="np. Final, Semifinal, QF">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Ranking PSA przed</label>
                            <input type="number" name="psa_ranking_before" class="form-control" min="1" placeholder="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ranking PSA po</label>
                            <input type="number" name="psa_ranking_after" class="form-control" min="1" placeholder="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Miejsce końcowe</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control" placeholder="np. U19, Senior">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-circle me-1"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>
