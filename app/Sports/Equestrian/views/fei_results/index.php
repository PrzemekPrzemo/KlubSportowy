<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-line me-1"></i> Wyniki FEI</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal"><i class="bi bi-plus-circle"></i> Dodaj wynik</button>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2">
        <div class="col-md-4">
            <select name="discipline" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Wszystkie dyscypliny</option>
                <?php foreach ($disciplines as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $filterDiscipline === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<?php if (!empty($ranking)): ?>
<div class="card mb-3">
    <div class="card-header"><strong><i class="bi bi-trophy"></i> Ranking FEI — <?= View::e($disciplines[$filterDiscipline] ?? '') ?></strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Poz</th><th>Zawodnik</th><th class="text-end">Starty</th><th class="text-end">Średni wynik</th><th class="text-end">Najlepsze miejsce</th></tr></thead>
            <tbody>
            <?php foreach ($ranking as $idx => $r): ?>
                <tr class="<?= $idx === 0 ? 'table-warning' : '' ?>">
                    <td><strong><?= $idx + 1 ?></strong></td>
                    <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                    <td class="text-end"><?= (int)$r['starts'] ?></td>
                    <td class="text-end"><?= View::e(number_format((float)$r['avg_score'], 2)) ?></td>
                    <td class="text-end"><?= View::e($r['best_rank'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Zawodnik</th><th>Koń</th><th>Dyscyplina</th><th>Zawody</th><th class="text-end">Score</th><th class="text-end">Błędy</th><th class="text-end">Czas</th><th class="text-end">Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: foreach ($results as $r): ?>
            <tr>
                <td><?= View::e($r['event_date'] ?? '—') ?></td>
                <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                <td><?= View::e($r['horse_name'] ?? '—') ?></td>
                <td><span class="badge bg-secondary"><?= View::e($disciplines[$r['discipline']] ?? $r['discipline']) ?></span></td>
                <td><?= View::e($r['event_name'] ?? '—') ?></td>
                <td class="text-end"><?= View::e($r['score'] ?? '—') ?></td>
                <td class="text-end"><?= View::e($r['faults_jumping'] ?? '—') ?></td>
                <td class="text-end"><?= View::e($r['time_seconds'] ?? '—') ?></td>
                <td class="text-end"><?= View::e($r['rank_position'] ?? '—') ?></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('equestrian/fei-results/' . (int)$r['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="resultModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= url('equestrian/fei-results/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowy wynik FEI</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
              <label class="form-label">Koń</label>
              <select name="horse_id" class="form-select">
                <option value="">— brak —</option>
                <?php foreach ($horses as $h): ?><option value="<?= (int)$h['id'] ?>"><?= View::e($h['name']) ?> (<?= View::e($h['fei_id'] ?? '?') ?>)</option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Dyscyplina *</label>
              <select name="discipline" class="form-select" required>
                <?php foreach ($disciplines as $k => $l): ?><option value="<?= $k ?>"><?= View::e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Nazwa zawodów</label><input type="text" name="event_name" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Data</label><input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-3"><label class="form-label">Score</label><input type="number" step="0.01" name="score" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Błędy (jumping)</label><input type="number" name="faults_jumping" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Czas (s)</label><input type="number" step="0.01" name="time_seconds" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Miejsce</label><input type="number" name="rank_position" class="form-control"></div>
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
