<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy me-1"></i> Leaderboard CrossFit</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#scoreModal" <?= empty($wods) ? 'disabled' : '' ?>><i class="bi bi-plus-circle"></i> Zapisz wynik</button>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label">Wybierz WOD</label>
            <select name="wod_id" class="form-select" onchange="this.form.submit()">
                <option value="">— wybierz WOD —</option>
                <?php foreach ($wods as $w): ?>
                    <option value="<?= (int)$w['id'] ?>" <?= ($selectedWod && (int)$selectedWod['id'] === (int)$w['id']) ? 'selected' : '' ?>>
                        <?= View::e($w['name']) ?> (<?= View::e($wodTypes[$w['type']] ?? $w['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<?php if ($selectedWod): ?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title mb-2"><?= View::e($selectedWod['name']) ?>
            <span class="badge bg-info text-dark"><?= View::e($wodTypes[$selectedWod['type']] ?? $selectedWod['type']) ?></span>
        </h5>
        <?php if (!empty($selectedWod['description'])): ?>
            <p class="text-muted small"><?= View::e($selectedWod['description']) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Top wyniki</strong></div>
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Poz</th><th>Zawodnik</th><th>Skala</th><th class="text-end">Czas</th><th class="text-end">Reps</th><th class="text-end">Load (kg)</th><th>Data</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($leaderboard)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak wyników dla tego WOD-a.</td></tr>
        <?php else: foreach ($leaderboard as $idx => $l): ?>
            <tr class="<?= $idx === 0 ? 'table-warning' : '' ?>">
                <td><strong><?= $idx + 1 ?></strong></td>
                <td><?= View::e($l['last_name']) ?> <?= View::e($l['first_name']) ?></td>
                <td><span class="badge bg-<?= $l['scaled_or_rx'] === 'RX' ? 'success' : 'secondary' ?>"><?= View::e($l['scaled_or_rx']) ?></span></td>
                <td class="text-end">
                    <?php if ($l['result_time_seconds'] !== null):
                        $s = (int)$l['result_time_seconds']; ?>
                        <?= sprintf('%d:%02d', intdiv($s, 60), $s % 60) ?>
                    <?php else: echo '—'; endif; ?>
                </td>
                <td class="text-end"><?= View::e($l['result_reps'] ?? '—') ?></td>
                <td class="text-end"><?= View::e($l['result_load_kg'] ?? '—') ?></td>
                <td><?= View::e($l['recorded_at']) ?></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('crossfit/leaderboard/' . (int)$l['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Wybierz WOD aby zobaczyć ranking.</div>
<?php endif; ?>

<div class="modal fade" id="scoreModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= url('crossfit/leaderboard/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowy wynik WOD</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Zawodnik *</label>
              <select name="member_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">WOD *</label>
              <select name="wod_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($wods as $w): ?><option value="<?= (int)$w['id'] ?>" <?= ($selectedWod && (int)$selectedWod['id'] === (int)$w['id']) ? 'selected' : '' ?>><?= View::e($w['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Skala</label>
              <select name="scaled_or_rx" class="form-select">
                <?php foreach ($levels as $k => $l): ?><option value="<?= $k ?>"><?= View::e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Czas (sek.)</label><input type="number" name="result_time_seconds" class="form-control" placeholder="np. 720"></div>
            <div class="col-md-4"><label class="form-label">Reps</label><input type="number" name="result_reps" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Load (kg)</label><input type="number" step="0.01" name="result_load_kg" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Data</label><input type="date" name="recorded_at" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-4 form-check mt-4 ps-4">
              <input class="form-check-input" type="checkbox" name="verified" value="1" id="vrf">
              <label class="form-check-label" for="vrf">Wynik zweryfikowany</label>
            </div>
            <div class="col-12"><label class="form-label">Notatki</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz</button>
        </div>
      </form>
    </div>
  </div>
</div>
