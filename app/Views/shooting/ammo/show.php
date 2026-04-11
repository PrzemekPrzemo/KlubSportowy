<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-5">
        <div class="card p-3">
            <h4><?= View::e($ammo['caliber']) ?></h4>
            <div class="display-6"><?= (int)$ammo['quantity'] ?> <small class="text-muted" style="font-size:1rem">szt.</small></div>
            <dl class="row small mt-3 mb-0">
                <dt class="col-5">Typ</dt><dd class="col-7"><?= View::e($ammo['type'] ?? '—') ?></dd>
                <dt class="col-5">Marka</dt><dd class="col-7"><?= View::e($ammo['brand'] ?? '—') ?></dd>
                <dt class="col-5">Cena jedn.</dt><dd class="col-7"><?= $ammo['unit_price'] !== null ? format_money($ammo['unit_price']) : '—' ?></dd>
                <dt class="col-5">Min. stan</dt><dd class="col-7"><?= $ammo['min_stock'] !== null ? (int)$ammo['min_stock'] : '—' ?></dd>
            </dl>
            <?php if (!empty($ammo['notes'])): ?>
                <hr>
                <small><?= nl2br(View::e($ammo['notes'])) ?></small>
            <?php endif; ?>
        </div>

        <div class="card p-3 mt-3">
            <h6 class="mb-3">Ruch magazynowy</h6>
            <form method="POST" action="<?= url('shooting/ammo/' . (int)$ammo['id'] . '/adjust') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small">Kierunek</label>
                    <select name="direction" class="form-select form-select-sm">
                        <option value="przyjecie">przyjęcie (+)</option>
                        <option value="wydanie">wydanie (-)</option>
                        <option value="korekta">korekta (=)</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Ilość</label>
                    <input type="number" name="quantity" min="1" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Zawodnik (przy wydaniu)</label>
                    <select name="member_id" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Notatka</label>
                    <input type="text" name="notes" class="form-control form-control-sm">
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> Zapisz ruch</button>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card p-3">
            <h6>Historia transakcji</h6>
            <?php if (empty($txs)): ?>
                <div class="text-muted small">Brak transakcji.</div>
            <?php else: ?>
                <table class="table table-sm">
                    <thead><tr><th>Data</th><th>Kierunek</th><th class="text-end">Ilość</th><th>Zawodnik</th><th>Opis</th></tr></thead>
                    <tbody>
                    <?php foreach ($txs as $t): ?>
                        <tr>
                            <td><small><?= format_datetime($t['created_at']) ?></small></td>
                            <td><span class="badge bg-<?= $t['direction']==='przyjecie'?'success':($t['direction']==='wydanie'?'warning':'info') ?>"><?= View::e($t['direction']) ?></span></td>
                            <td class="text-end"><?= (int)$t['quantity'] ?></td>
                            <td><small><?= View::e(($t['last_name'] ?? '') . ' ' . ($t['first_name'] ?? '')) ?></small></td>
                            <td><small><?= View::e($t['notes'] ?? '') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
