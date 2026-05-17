<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-card-list me-1"></i> Rejestr koni FEI</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#horseModal"><i class="bi bi-plus-circle"></i> Dodaj konia</button>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Imię</th><th>Rasa</th><th>Rok ur.</th><th>FEI ID</th><th>Właściciel</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($horses)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak koni.</td></tr>
        <?php else: foreach ($horses as $h): ?>
            <tr>
                <td><strong><?= View::e($h['name']) ?></strong></td>
                <td><?= View::e($h['breed'] ?? '—') ?></td>
                <td><?= View::e($h['birth_year'] ?? '—') ?></td>
                <td><code><?= View::e($h['fei_id'] ?? '—') ?></code></td>
                <td><?= $h['owner_member_id'] ? View::e($h['owner_last'] . ' ' . $h['owner_first']) : '—' ?></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('equestrian/fei-horses/' . (int)$h['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć konia?')">
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

<div class="modal fade" id="horseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= url('equestrian/fei-horses/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowy koń (FEI)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Imię konia *</label><input type="text" name="name" class="form-control" required></div>
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Rasa</label><input type="text" name="breed" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Rok urodzenia</label><input type="number" name="birth_year" class="form-control" min="1980" max="<?= date('Y') ?>"></div>
          </div>
          <div class="mb-2 mt-2"><label class="form-label">FEI ID</label><input type="text" name="fei_id" class="form-control"></div>
          <div class="mb-2">
            <label class="form-label">Właściciel</label>
            <select name="owner_member_id" class="form-select">
              <option value="">— brak —</option>
              <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Notatki</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz</button>
        </div>
      </form>
    </div>
  </div>
</div>
