<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-steps me-1"></i> Regaty multi-race</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#raceModal"><i class="bi bi-plus-circle"></i> Dodaj wyścig</button>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input type="number" name="tournament_id" value="<?= View::e($filterTournamentId) ?>" class="form-control form-control-sm" placeholder="Tournament ID"></div>
        <div class="col-md-3">
            <select name="boat_class" class="form-select form-select-sm">
                <option value="">Wszystkie klasy</option>
                <?php foreach ($boatClasses as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $filterBoatClass === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><input type="number" name="drop_worst" value="<?= (int)$dropWorst ?>" min="0" class="form-control form-control-sm" placeholder="Drop worst N"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary">Filtruj</button></div>
    </div>
</form>

<div class="row">
    <div class="col-lg-7">
        <h5 class="mb-2"><i class="bi bi-list"></i> Wyścigi</h5>
        <div class="card">
            <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Data</th><th>Zawodnik</th><th>Klasa</th><th class="text-end">#</th><th class="text-end">Poz.</th><th class="text-end">Pkt</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php if (empty($races)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">Brak wyścigów.</td></tr>
                <?php else: foreach ($races as $r): ?>
                    <tr>
                        <td><?= View::e($r['race_date'] ?? '—') ?></td>
                        <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                        <td><?= View::e($r['boat_class'] ?? '—') ?></td>
                        <td class="text-end"><?= (int)$r['race_number'] ?></td>
                        <td class="text-end"><?= View::e($r['position'] ?? '—') ?></td>
                        <td class="text-end"><strong><?= View::e($r['points'] ?? '—') ?></strong></td>
                        <td><span class="badge bg-secondary"><?= View::e($r['status']) ?></span></td>
                        <td class="text-end">
                            <form method="POST" action="<?= url('sailing/regatta/' . (int)$r['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć?')">
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
    </div>
    <div class="col-lg-5">
        <h5 class="mb-2"><i class="bi bi-trophy"></i> Klasyfikacja (low-point, drop worst <?= (int)$dropWorst ?>)</h5>
        <div class="card">
            <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Poz</th><th>Zawodnik</th><th class="text-end">Wyścigi</th><th class="text-end">Suma</th><th class="text-end">Drop</th><th class="text-end">Netto</th></tr>
                </thead>
                <tbody>
                <?php if (empty($standings)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Brak danych.</td></tr>
                <?php else: foreach ($standings as $idx => $s): ?>
                    <tr class="<?= $idx === 0 ? 'table-warning' : '' ?>">
                        <td><strong><?= $idx + 1 ?></strong></td>
                        <td><?= View::e($s['last_name']) ?> <?= View::e($s['first_name']) ?></td>
                        <td class="text-end"><?= (int)$s['races_count'] ?></td>
                        <td class="text-end"><?= View::e($s['total_raw']) ?></td>
                        <td class="text-end text-muted"><?= View::e($s['drop_worst']) ?></td>
                        <td class="text-end"><strong><?= View::e($s['total_net']) ?></strong></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="raceModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= url('sailing/regatta/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowy wyścig regatowy</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Zawodnik *</label>
              <select name="member_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Klasa</label>
              <select name="boat_class" class="form-select">
                <option value="">—</option>
                <?php foreach ($boatClasses as $k => $l): ?><option value="<?= $k ?>"><?= View::e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Tournament ID</label><input type="number" name="tournament_id" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Race # *</label><input type="number" name="race_number" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Pozycja</label><input type="number" name="position" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Punkty</label><input type="number" step="0.01" name="points" class="form-control"></div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>"><?= View::e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Data wyścigu</label><input type="date" name="race_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-4"><label class="form-label">Wiatr (knots)</label><input type="number" name="weather_wind_knots" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Fala (cm)</label><input type="number" name="weather_wave_height_cm" class="form-control"></div>
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
