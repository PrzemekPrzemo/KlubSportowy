<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-person-badge me-2"></i>Zawodnicy PZJ</h3>
    <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#riderForm">
        <i class="bi bi-plus-circle me-1"></i> Nadaj licencję
    </button>
</div>

<?php if (!empty($expiringSoon)): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Uwaga:</strong> licencje wygasają w ciągu 30 dni:
        <?php foreach ($expiringSoon as $r): ?>
            <span class="badge bg-warning text-dark">
                <?= View::e($r['first_name']) ?> <?= View::e($r['last_name']) ?>
                — <?= (int)$r['days_to_expiry'] ?> dni
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div id="riderForm" class="collapse mb-3">
    <div class="card p-3">
        <form method="POST" action="<?= url('equestrian/riders/store') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-5">
                <label class="form-label">Członek klubu *</label>
                <select name="member_id" class="form-select" required>
                    <option value="">— wybierz członka —</option>
                    <?php foreach ($availableMembers as $m): ?>
                        <option value="<?= (int)$m['id'] ?>">
                            <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                            <?php if (!empty($m['member_number'])): ?>(<?= View::e($m['member_number']) ?>)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Klasa licencji</label>
                <select name="license_class" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($licenseClasses as $k => $label): ?>
                        <option value="<?= $k ?>"><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Numer licencji</label>
                <input type="text" name="license_no" class="form-control" maxlength="40">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ważna do</label>
                <input type="date" name="license_valid_until" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Dyscyplina główna</label>
                <select name="discipline_main" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($disciplines as $k => $label): ?>
                        <option value="<?= $k ?>"><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Waga (kg)</label>
                <input type="number" step="0.1" min="20" max="200" name="weight_kg" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Wzrost (cm)</label>
                <input type="number" min="100" max="220" name="height_cm" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ręka dominująca</label>
                <select name="handedness" class="form-select">
                    <option value="">—</option>
                    <option value="right">Prawa</option>
                    <option value="left">Lewa</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> Nadaj licencję</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Zawodnik</th>
                <th>Klasa</th>
                <th>Numer</th>
                <th>Dyscyplina</th>
                <th>Ważna do</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($riders)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">
                    Brak zawodników z licencją PZJ.
                </td></tr>
            <?php else: foreach ($riders as $r):
                $expiringSoonClass = !empty($r['days_to_expiry']) && $r['days_to_expiry'] >= 0 && $r['days_to_expiry'] < 30
                    ? 'text-warning' : ($r['days_to_expiry'] !== null && $r['days_to_expiry'] < 0 ? 'text-danger' : 'text-muted');
                $statusBadge = match ($r['status']) {
                    'aktywny'    => 'success',
                    'zawieszony' => 'warning',
                    'wycofany'   => 'secondary',
                    default      => 'secondary',
                };
            ?>
                <tr>
                    <td>
                        <strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong>
                        <?php if (!empty($r['member_number'])): ?>
                            <small class="text-muted"><?= View::e($r['member_number']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($r['license_class'])): ?>
                            <span class="badge bg-info"><?= View::e($r['license_class']) ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="font-monospace small"><?= View::e($r['license_no'] ?? '—') ?></td>
                    <td><?= View::e($disciplines[$r['discipline_main'] ?? ''] ?? '—') ?></td>
                    <td class="<?= $expiringSoonClass ?>">
                        <?= View::e($r['license_valid_until'] ?? '—') ?>
                        <?php if ($r['days_to_expiry'] !== null): ?>
                            <small>(<?= (int)$r['days_to_expiry'] ?> dni)</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= $statusBadge ?>"><?= View::e($statusOptions[$r['status']] ?? $r['status']) ?></span></td>
                    <td class="text-end">
                        <form method="POST" action="<?= url('equestrian/riders/' . (int)$r['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć licencję zawodnika?')" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
