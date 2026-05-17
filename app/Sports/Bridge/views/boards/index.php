<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-grid-3x3-gap me-1"></i> Rozdania (boards) — IMP/MP</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#boardModal"><i class="bi bi-plus-circle"></i> Dodaj rozdanie</button>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Board #</th><th>Para</th><th>Contract</th><th>Decl.</th><th class="text-end">Result</th><th class="text-end">IMP</th><th class="text-end">MP</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($boards)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak rozdań.</td></tr>
        <?php else: foreach ($boards as $b): ?>
            <tr>
                <td><?= View::e($b['played_at']) ?></td>
                <td><strong><?= View::e($b['board_number']) ?></strong></td>
                <td>
                  <?= View::e($b['pair_name'] ?? '') ?>
                  <span class="text-muted small d-block"><?= View::e($b['north_last']) ?> / <?= View::e($b['south_last']) ?></span>
                </td>
                <td><?= View::e($b['contract'] ?? '—') ?></td>
                <td><?= View::e($b['declarer'] ?? '—') ?></td>
                <td class="text-end"><?= $b['result'] !== null ? View::e($b['result']) : '—' ?></td>
                <td class="text-end"><?= $b['imp_score'] !== null ? View::e($b['imp_score']) : '—' ?></td>
                <td class="text-end"><?= $b['mp_score'] !== null ? View::e($b['mp_score']) : '—' ?></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('bridge/boards/' . (int)$b['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć rozdanie?')">
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

<div class="modal fade" id="boardModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= url('bridge/boards/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Dodaj rozdanie</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Para *</label>
              <select name="pair_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($pairs as $p): ?>
                  <option value="<?= (int)$p['id'] ?>"><?= View::e($p['pair_name'] ?? ($p['north_last'] . '/' . $p['south_last'])) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Board # *</label><input type="number" name="board_number" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Tournament ID</label><input type="number" name="tournament_id" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Contract</label><input type="text" name="contract" class="form-control" placeholder="np. 4S, 3NT"></div>
            <div class="col-md-2"><label class="form-label">Declarer</label><select name="declarer" class="form-select"><option value="">—</option><option>N</option><option>S</option><option>E</option><option>W</option></select></div>
            <div class="col-md-2"><label class="form-label">Result</label><input type="number" name="result" class="form-control" placeholder="+1/-2"></div>
            <div class="col-md-2"><label class="form-label">IMP</label><input type="number" name="imp_score" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">MP</label><input type="number" step="0.01" name="mp_score" class="form-control"></div>
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
