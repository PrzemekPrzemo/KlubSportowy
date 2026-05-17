<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people me-1"></i> Pary brydżowe (N-S) — Masterpoints</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pairModal"><i class="bi bi-plus-circle"></i> Dodaj parę</button>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>#</th><th>Nazwa pary</th><th>N (north)</th><th>S (south)</th><th class="text-end">Masterpoints</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pairs)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak par.</td></tr>
        <?php else: foreach ($pairs as $idx => $p): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= View::e($p['pair_name'] ?? '—') ?></td>
                <td><?= View::e($p['north_last']) ?> <?= View::e($p['north_first']) ?></td>
                <td><?= View::e($p['south_last']) ?> <?= View::e($p['south_first']) ?></td>
                <td class="text-end"><strong><?= View::e($p['masterpoints']) ?></strong></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('bridge/pairs/' . (int)$p['id'] . '/mp') ?>" class="d-inline-flex gap-1">
                        <?= csrf_field() ?>
                        <input type="number" step="0.01" name="masterpoints_delta" placeholder="+/-" class="form-control form-control-sm" style="width:90px">
                        <button class="btn btn-sm btn-outline-success" title="Dodaj MP"><i class="bi bi-plus-lg"></i></button>
                    </form>
                    <form method="POST" action="<?= url('bridge/pairs/' . (int)$p['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć parę?')">
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

<div class="modal fade" id="pairModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= url('bridge/pairs/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Dodaj parę N-S</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Gracz N (north) *</label>
            <select name="member_north_id" class="form-select" required>
              <option value="">— wybierz —</option>
              <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Gracz S (south) *</label>
            <select name="member_south_id" class="form-select" required>
              <option value="">— wybierz —</option>
              <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Nazwa pary</label><input type="text" name="pair_name" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Startowe MP</label><input type="number" step="0.01" name="masterpoints" class="form-control" value="0"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz</button>
        </div>
      </form>
    </div>
  </div>
</div>
