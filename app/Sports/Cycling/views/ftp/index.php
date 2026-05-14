<?php
use App\Helpers\View;
use App\Sports\Cycling\Models\CyclingFtpModel;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-lightning-fill text-warning me-2"></i>Testy FTP — Kolarstwo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ftpModal">
        <i class="bi bi-plus-circle"></i> Dodaj test
    </button>
</div>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    <strong>FTP (Functional Threshold Power)</strong> — maksymalna moc, jaką kolarz może utrzymać przez godzinę. Pomiar w watach, często normalizowany do masy ciała (W/kg).
</div>

<form method="GET" class="mb-3 d-flex gap-2">
    <select name="member" class="form-select form-select-sm">
        <option value="">Wszyscy zawodnicy</option>
        <?php foreach ($members as $mm): ?>
            <option value="<?= (int)$mm['id'] ?>" <?= $memberFilter === (int)$mm['id'] ? 'selected' : '' ?>>
                <?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th><th>Zawodnik</th><th>FTP (W)</th><th>Waga (kg)</th>
                    <th>W/kg</th><th>Kategoria</th><th>Protokół</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tests)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak testów.</td></tr>
            <?php else: foreach ($tests as $t):
                $wpkg = CyclingFtpModel::wattsPerKg((int)$t['ftp_watts'], $t['weight_kg']);
                $cat  = $wpkg ? CyclingFtpModel::fitnessCategory($wpkg) : null;
            ?>
                <tr>
                    <td class="small text-muted"><?= View::e($t['test_date']) ?></td>
                    <td>
                        <strong><?= View::e($t['last_name'] . ' ' . $t['first_name']) ?></strong>
                        <small class="text-muted">#<?= View::e($t['member_number']) ?></small>
                    </td>
                    <td class="font-monospace fw-bold text-warning"><?= (int)$t['ftp_watts'] ?> W</td>
                    <td class="small"><?= $t['weight_kg'] ? View::e($t['weight_kg']) : '—' ?></td>
                    <td class="font-monospace fw-bold">
                        <?= $wpkg ? number_format($wpkg, 2) : '—' ?>
                    </td>
                    <td><?php if ($cat): ?><span class="badge bg-primary"><?= View::e($cat) ?></span><?php endif; ?></td>
                    <td><small class="text-muted"><?= View::e($protocols[$t['protocol']] ?? $t['protocol']) ?></small></td>
                    <td>
                        <form method="POST" action="<?= url('cycling/ftp/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<!-- Modal -->
<div class="modal fade" id="ftpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('cycling/ftp/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj test FTP</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Data testu</label>
                            <input type="date" name="test_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Protokół</label>
                            <select name="protocol" class="form-select">
                                <?php foreach ($protocols as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">FTP (W)</label>
                            <input type="number" name="ftp_watts" class="form-control" min="50" max="600" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Waga (kg) — opcjonalna</label>
                            <input type="number" step="0.1" name="weight_kg" class="form-control" min="30" max="200">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
