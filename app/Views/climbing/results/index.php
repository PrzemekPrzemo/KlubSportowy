<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki zawodów — Wspinaczka sportowa</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Zawody</th><th>Data</th><th>Dyscyplina</th><th>Ocena/Stopień</th><th>Wynik</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($results as $r):
                $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                $cat = $r['category'] ?? null;
                if ($cat === 'speed' && isset($r['time_seconds']) && $r['time_seconds'] !== null) {
                    $ts = (float)$r['time_seconds'];
                    $sec = (int)$ts;
                    $cs = round(($ts - $sec) * 100);
                    $wynik = sprintf('%d.%02d s', $sec, $cs);
                } elseif (isset($r['score_tops']) || isset($r['score_zones'])) {
                    $tops = $r['score_tops'] !== null ? $r['score_tops'].' top' : null;
                    $zones = $r['score_zones'] !== null ? $r['score_zones'].' str.' : null;
                    $wynik = implode(' / ', array_filter([$tops, $zones])) ?: '—';
                } else {
                    $wynik = '—';
                }
            ?>
                <tr>
                    <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                    <td><?= View::e($r['competition_name']) ?></td>
                    <td><?= View::e($r['competition_date']) ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($categories[$r['category']] ?? ($r['category'] ?? '—')) ?></span></td>
                    <td><?= $r['difficulty_grade'] ? View::e($r['difficulty_grade']) : '—' ?></td>
                    <td><?= View::e($wynik) ?></td>
                    <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('climbing/results/'.(int)$r['id'].'/delete') ?>"
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
            <form method="POST" action="<?= url('climbing/results/store') ?>">
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
                            <label class="form-label">Dyscyplina</label>
                            <select name="category" id="climbingCategory" class="form-select">
                                <option value="">— ogólna —</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ocena / Stopień trudności</label>
                            <input type="text" name="difficulty_grade" class="form-control" placeholder="np. 7a, 8b+">
                        </div>
                    </div>
                    <div id="climbingSpeedFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Czas (sekundy, np. 6.43)</label>
                            <input type="number" name="time_seconds" class="form-control" min="0" step="0.01" placeholder="np. 6.43">
                        </div>
                    </div>
                    <div id="climbingTopZoneFields" style="display:none;">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Topy (tops)</label>
                                <input type="number" name="score_tops" class="form-control" min="0" max="99">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Strefy (zones)</label>
                                <input type="number" name="score_zones" class="form-control" min="0" max="99">
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
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
<script>
(function() {
    var sel = document.getElementById('climbingCategory');
    var speedDiv = document.getElementById('climbingSpeedFields');
    var topZoneDiv = document.getElementById('climbingTopZoneFields');
    function updateFields() {
        var v = sel ? sel.value : '';
        if (speedDiv) speedDiv.style.display = (v === 'speed') ? '' : 'none';
        if (topZoneDiv) topZoneDiv.style.display = (v === 'lead' || v === 'boulder' || v === 'combined') ? '' : 'none';
    }
    if (sel) sel.addEventListener('change', updateFields);
    updateFields();
})();
</script>
