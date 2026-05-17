<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bookmark-star me-1"></i> Biblioteka dróg (IFSC)</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#routeModal"><i class="bi bi-plus-circle"></i> Dodaj drogę</button>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2">
        <div class="col-md-3">
            <select name="discipline" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Wszystkie dyscypliny</option>
                <?php foreach ($disciplines as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $filterDiscipline === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" name="retired" value="1" id="retired" onchange="this.form.submit()" <?= $includeRetired ? 'checked' : '' ?>>
            <label class="form-check-label" for="retired">Pokaż zdjęte drogi</label>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Nazwa</th><th>Dyscyplina</th><th>YDS</th><th>French</th><th>Lokalizacja</th><th>Setter</th><th>Set date</th><th class="text-end">Próby/Tops</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($routes)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak dróg w bibliotece.</td></tr>
        <?php else: foreach ($routes as $r): ?>
            <tr class="<?= !empty($r['retired_date']) ? 'text-muted' : '' ?>">
                <td><strong><?= View::e($r['route_name']) ?></strong></td>
                <td><span class="badge bg-secondary"><?= View::e($disciplines[$r['discipline']] ?? $r['discipline']) ?></span></td>
                <td><?= View::e($r['grade_yds'] ?? '—') ?></td>
                <td><?= View::e($r['grade_french'] ?? '—') ?></td>
                <td><?= View::e($r['location_name'] ?? '—') ?></td>
                <td><?= View::e($r['setter'] ?? '—') ?></td>
                <td><?= View::e($r['set_date'] ?? '—') ?></td>
                <td class="text-end"><?= (int)$r['attempts_count'] ?> / <strong><?= (int)$r['tops_count'] ?></strong></td>
                <td class="text-end">
                    <?php if (empty($r['retired_date'])): ?>
                    <form method="POST" action="<?= url('climbing/library/' . (int)$r['id'] . '/retire') ?>" class="d-inline" onsubmit="return confirm('Zdjąć drogę?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-warning" title="Zdejmij"><i class="bi bi-archive"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('climbing/library/' . (int)$r['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć drogę?')">
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

<div class="modal fade" id="routeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= url('climbing/library/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Dodaj drogę</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Nazwa drogi *</label><input type="text" name="route_name" class="form-control" required></div>
          <div class="mb-2">
            <label class="form-label">Dyscyplina</label>
            <select name="discipline" class="form-select">
              <?php foreach ($disciplines as $k => $l): ?><option value="<?= $k ?>"><?= View::e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">YDS (5.10a, V5)</label><input type="text" name="grade_yds" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">French (6a, 7b+)</label><input type="text" name="grade_french" class="form-control"></div>
          </div>
          <div class="mb-2 mt-2"><label class="form-label">Lokalizacja</label><input type="text" name="location_name" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Setter</label><input type="text" name="setter" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Set date</label><input type="date" name="set_date" class="form-control"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz</button>
        </div>
      </form>
    </div>
  </div>
</div>
