<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clipboard-data me-1"></i> D-score + E-score — Gimnastyka</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#perfModal">
        <i class="bi bi-plus-circle"></i> Dodaj występ
    </button>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Data</th><th>Zawodnik</th><th>Konkurencja</th><th>Przyrząd</th>
                <th class="text-end">D</th><th class="text-end">E</th><th class="text-end">Deduct.</th>
                <th class="text-end">Total</th><th class="text-end">Miejsce</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($performances)): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Brak występów.</td></tr>
        <?php else: foreach ($performances as $p): ?>
            <tr>
                <td><?= View::e($p['performed_at']) ?></td>
                <td><strong><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></strong></td>
                <td><?= View::e($p['routine_type'] ?? '—') ?></td>
                <td><?= View::e($p['apparatus'] ?? '—') ?></td>
                <td class="text-end"><?= $p['difficulty_score'] !== null ? View::e($p['difficulty_score']) : '—' ?></td>
                <td class="text-end"><?= $p['execution_score'] !== null ? View::e($p['execution_score']) : '—' ?></td>
                <td class="text-end text-danger"><?= View::e($p['deductions']) ?></td>
                <td class="text-end"><strong><?= $p['total_score'] !== null ? View::e($p['total_score']) : '—' ?></strong></td>
                <td class="text-end"><?= $p['rank_position'] !== null ? View::e($p['rank_position']) : '—' ?></td>
                <td class="text-end">
                    <a href="<?= url('gymnastics/scoring/' . (int)$p['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                    <form method="POST" action="<?= url('gymnastics/scoring/' . (int)$p['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="perfModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= url('gymnastics/scoring/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowy występ (D/E)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
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
              <label class="form-label">Przyrząd</label>
              <select name="apparatus" class="form-select">
                <option value="">— wybierz —</option>
                <optgroup label="Kobiety">
                  <?php foreach ($apparatus_w as $k => $l): ?>
                    <option value="<?= $k ?>"><?= View::e($l) ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="Mężczyźni">
                  <?php foreach ($apparatus_m as $k => $l): ?>
                    <option value="<?= $k ?>"><?= View::e($l) ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Konkurencja (routine_type)</label><input type="text" name="routine_type" class="form-control" placeholder="all_around / per_apparatus / team"></div>
            <div class="col-md-6"><label class="form-label">Miejsce</label><input type="number" name="rank_position" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">D-score</label><input type="number" step="0.01" name="difficulty_score" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">E-score</label><input type="number" step="0.01" name="execution_score" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Deductions</label><input type="number" step="0.01" name="deductions" class="form-control" value="0"></div>
            <div class="col-12"><label class="form-label">Notatki</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz występ</button>
        </div>
      </form>
    </div>
  </div>
</div>
