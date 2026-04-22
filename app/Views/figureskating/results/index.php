<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-star-half text-primary me-2"></i>Wyniki — Łyżwiarstwo figurowe</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= url('figureskating/results') ?>" class="btn btn-sm btn-<?= !$discFilter ? 'primary' : 'outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($disciplines as $k => $v): ?>
        <a href="?discipline=<?= urlencode($k) ?>" class="btn btn-sm btn-<?= $discFilter === $k ? 'primary' : 'outline-secondary' ?>"><?= View::e($v) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Dyscyplina</th><th>Level</th><th>SP</th><th>FS</th><th>Total</th><th>#</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: foreach ($results as $r): ?>
                <tr>
                    <td class="small"><?= View::e($r['event_date']) ?></td>
                    <td>
                        <strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong>
                        <?php if ($r['partner_name']): ?><small class="text-muted d-block">+ <?= View::e($r['partner_name']) ?></small><?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary"><?= View::e($disciplines[$r['discipline']] ?? $r['discipline']) ?></span></td>
                    <td><span class="badge bg-dark"><?= View::e($levels[$r['level']] ?? $r['level']) ?></span></td>
                    <td class="font-monospace small"><?= $r['sp_total'] !== null ? number_format((float)$r['sp_total'], 2) : '—' ?></td>
                    <td class="font-monospace small"><?= $r['fs_total'] !== null ? number_format((float)$r['fs_total'], 2) : '—' ?></td>
                    <td class="font-monospace fw-bold text-success"><?= $r['total_score'] !== null ? number_format((float)$r['total_score'], 2) : '—' ?></td>
                    <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                    <td>
                        <form method="POST" action="<?= url('figureskating/results/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="resModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('figureskating/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj wynik</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Partner (pair/dance)</label>
                            <input type="text" name="partner_name" class="form-control" placeholder="Imię i nazwisko partnera/ki">
                        </div>
                        <div class="col-4"><label class="form-label">Dyscyplina</label>
                            <select name="discipline" class="form-select">
                                <?php foreach ($disciplines as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label">Level</label>
                            <select name="level" class="form-select">
                                <?php foreach ($levels as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label">Kategoria</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="col-6"><label class="form-label">Zawody</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="col-6"><label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12"><label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control">
                        </div>
                        <div class="col-3"><label class="form-label">SP TES</label><input type="number" step="0.01" name="sp_tes" class="form-control"></div>
                        <div class="col-3"><label class="form-label">SP PCS</label><input type="number" step="0.01" name="sp_pcs" class="form-control"></div>
                        <div class="col-3"><label class="form-label">FS TES</label><input type="number" step="0.01" name="fs_tes" class="form-control"></div>
                        <div class="col-3"><label class="form-label">FS PCS</label><input type="number" step="0.01" name="fs_pcs" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Deductions (−)</label><input type="number" step="0.1" name="deductions" class="form-control" value="0"></div>
                        <div class="col-4"><label class="form-label">Miejsce</label><input type="number" name="place" class="form-control" min="1"></div>
                        <div class="col-12"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                    <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle"></i> Total = SP_total + FS_total − deductions (auto)</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
