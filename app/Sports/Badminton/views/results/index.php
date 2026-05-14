<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki zawodów — Badminton</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Zawody</th><th>Data</th><th>Kat.</th><th>Sety (W:L)</th><th>Ranking Δ</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($results as $r):
                $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                $sets = (isset($r['sets_won']) && $r['sets_won'] !== null && isset($r['sets_lost']) && $r['sets_lost'] !== null)
                    ? $r['sets_won'].':'.$r['sets_lost'] : '—';
                $rankDelta = '—';
                if (isset($r['ranking_points_before']) && $r['ranking_points_before'] !== null
                    && isset($r['ranking_points_after']) && $r['ranking_points_after'] !== null) {
                    $diff = (int)$r['ranking_points_after'] - (int)$r['ranking_points_before'];
                    $sign = $diff >= 0 ? '+' : '';
                    $rankDelta = $sign.$diff.' ('.$r['ranking_points_before'].'→'.$r['ranking_points_after'].')';
                }
            ?>
                <tr>
                    <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                    <td><?= View::e($r['competition_name']) ?></td>
                    <td><?= View::e($r['competition_date']) ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($categories[$r['category']] ?? ($r['category'] ?? '—')) ?></span></td>
                    <td><?= View::e($sets) ?></td>
                    <td><small><?= View::e($rankDelta) ?></small></td>
                    <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= url('badminton/results/'.(int)$r['id']) ?>"
                               class="btn btn-sm btn-outline-secondary" title="Szczegóły">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= url('badminton/results/'.(int)$r['id'].'/edit') ?>"
                               class="btn btn-sm btn-outline-primary" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="<?= url('badminton/results/'.(int)$r['id'].'/delete') ?>"
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
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('badminton/results/store') ?>">
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
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sety wygrane</label>
                            <input type="number" name="sets_won" class="form-control" min="0" max="7">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sety przegrane</label>
                            <input type="number" name="sets_lost" class="form-control" min="0" max="7">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ranking przed</label>
                            <input type="number" name="ranking_points_before" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ranking po</label>
                            <input type="number" name="ranking_points_after" class="form-control" min="0">
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
