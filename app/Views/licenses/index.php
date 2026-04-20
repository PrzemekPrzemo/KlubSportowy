<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Licencje sportowe</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#licenseModal">
        <i class="bi bi-plus-circle"></i> Dodaj licencję
    </button>
</div>

<?php if (!empty($expiring)): ?>
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <div>
        <strong><?= count($expiring) ?> licencji</strong> wygasa w ciągu 30 dni.
        <?php foreach ($expiring as $e): ?>
            <span class="badge bg-warning text-dark ms-1">
                <?= View::e($e['last_name']) ?> <?= View::e($e['first_name']) ?>
                (<?= View::e($e['sport_key']) ?>, do <?= View::e($e['valid_to']) ?>)
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Filtr po sporcie -->
<div class="mb-3">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0">Sport:</label>
        <select name="sport" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">— wszystkie —</option>
            <?php foreach ($sports as $key => $s): ?>
                <option value="<?= View::e($key) ?>" <?= $filterSport === $key ? 'selected' : '' ?>>
                    <?= View::e($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Nr</th><th>Sport</th><th>Nr licencji</th><th>Klasa</th><th>Federacja</th><th>Ważna od</th><th>Ważna do</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($licenses)): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Brak licencji.</td></tr>
        <?php else: ?>
            <?php foreach ($licenses as $l):
                $isExpired = $l['valid_to'] && $l['valid_to'] < date('Y-m-d');
                $expiringSoon = $l['valid_to'] && $l['valid_to'] <= date('Y-m-d', strtotime('+30 days')) && !$isExpired;
                $statusColors = ['active' => 'success', 'expired' => 'secondary', 'suspended' => 'danger'];
                $statusLabels = ['active' => 'aktywna', 'expired' => 'wygasła', 'suspended' => 'zawieszona'];
            ?>
                <tr class="<?= $isExpired ? 'table-secondary' : ($expiringSoon ? 'table-warning' : '') ?>">
                    <td><strong><?= View::e($l['last_name']) ?> <?= View::e($l['first_name']) ?></strong></td>
                    <td class="text-muted small"><?= View::e($l['member_number']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= View::e($sports[$l['sport_key']]['name'] ?? $l['sport_key']) ?></span></td>
                    <td class="font-monospace"><?= View::e($l['license_number']) ?></td>
                    <td><?= View::e($l['license_class'] ?? '—') ?></td>
                    <td><?= View::e($l['federation'] ?? '—') ?></td>
                    <td><?= View::e($l['valid_from']) ?></td>
                    <td><?= View::e($l['valid_to'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $statusColors[$l['status']] ?? 'secondary' ?>">
                            <?= View::e($statusLabels[$l['status']] ?? $l['status']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('sport-licenses/'.(int)$l['id'].'/delete') ?>"
                              onsubmit="return confirm('Usunąć licencję?')">
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

<!-- Modal: Dodaj licencję -->
<div class="modal fade" id="licenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('sport-licenses/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-card-checklist me-1"></i> Dodaj licencję sportową</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
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
                            <label class="form-label">Sport *</label>
                            <select name="sport_key" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($sports as $key => $s): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Numer licencji *</label>
                            <input type="text" name="license_number" class="form-control" required placeholder="np. PZJ/2024/00123">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Klasa / kategoria</label>
                            <input type="text" name="license_class" class="form-control" placeholder="np. Senior, Junior, Trener">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Federacja</label>
                            <input type="text" name="federation" class="form-control" placeholder="np. PZJ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ważna od *</label>
                            <input type="date" name="valid_from" class="form-control" value="<?= date('Y-01-01') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ważna do</label>
                            <input type="date" name="valid_to" class="form-control" value="<?= date('Y-12-31') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-card-checklist me-1"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
