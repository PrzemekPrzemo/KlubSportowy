<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clipboard-data me-1"></i> Skating system — Taniec sportowy</h4>
    <div>
        <a href="<?= url('dance_sport/scoring/finalists') ?>" class="btn btn-outline-warning"><i class="bi bi-award"></i> Finaliści</a>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#perfModal"><i class="bi bi-plus-circle"></i> Nowy występ</button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Data</th><th>Tancerz</th><th>Routine</th><th class="text-end">Tech</th><th class="text-end">Pres</th><th class="text-end">Total</th><th class="text-end">Miejsce</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($performances)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak występów.</td></tr>
        <?php else: foreach ($performances as $p): ?>
            <tr>
                <td><?= View::e($p['performed_at']) ?></td>
                <td><strong><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></strong></td>
                <td><?= View::e($routines[$p['routine_type']] ?? ($p['routine_type'] ?? '—')) ?></td>
                <td class="text-end"><?= $p['technical_score'] !== null ? View::e($p['technical_score']) : '—' ?></td>
                <td class="text-end"><?= $p['presentation_score'] !== null ? View::e($p['presentation_score']) : '—' ?></td>
                <td class="text-end"><strong><?= $p['total_score'] !== null ? View::e($p['total_score']) : '—' ?></strong></td>
                <td class="text-end"><?= $p['rank_position'] !== null ? View::e($p['rank_position']) : '—' ?></td>
                <td class="text-end">
                    <a href="<?= url('dance_sport/scoring/' . (int)$p['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                    <form method="POST" action="<?= url('dance_sport/scoring/' . (int)$p['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć?')">
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
      <form method="POST" action="<?= url('dance_sport/scoring/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowy występ (skating system)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Tancerz/Para *</label>
              <select name="member_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?>
                  <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Routine</label>
              <select name="routine_type" class="form-select">
                <?php foreach ($routines as $k => $l): ?>
                  <option value="<?= $k ?>"><?= View::e($l) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Technical</label><input type="number" step="0.01" name="technical_score" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Presentation</label><input type="number" step="0.01" name="presentation_score" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Deductions</label><input type="number" step="0.01" name="deductions" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label">Miejsce</label><input type="number" name="rank_position" class="form-control"></div>
            <div class="col-md-8"><label class="form-label">Data wystąpu</label><input type="datetime-local" name="performed_at" class="form-control"></div>
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
