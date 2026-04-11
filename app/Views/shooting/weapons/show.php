<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-5">
        <div class="card p-3">
            <h5>
                <span class="badge bg-secondary"><?= View::e($weapon['category']) ?></span>
                <?= View::e($weapon['brand'] ?? '') ?> <?= View::e($weapon['model'] ?? '') ?>
            </h5>
            <dl class="row small mb-0">
                <dt class="col-5">Nr seryjny</dt><dd class="col-7"><code><?= View::e($weapon['serial_number']) ?></code></dd>
                <dt class="col-5">Kaliber</dt><dd class="col-7"><?= View::e($weapon['caliber'] ?? '—') ?></dd>
                <dt class="col-5">Rok prod.</dt><dd class="col-7"><?= View::e($weapon['production_year'] ?? '—') ?></dd>
                <dt class="col-5">Stan</dt><dd class="col-7"><?= View::e($weapon['condition_state']) ?></dd>
                <dt class="col-5">Zakup</dt><dd class="col-7"><?= format_date($weapon['purchase_date'] ?? null) ?></dd>
                <?php if (!empty($weapon['purchase_price'])): ?>
                    <dt class="col-5">Cena</dt><dd class="col-7"><?= format_money($weapon['purchase_price']) ?></dd>
                <?php endif; ?>
            </dl>
            <hr>
            <a href="<?= url('shooting/weapons/' . (int)$weapon['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Edytuj
            </a>
        </div>

        <div class="card p-3 mt-3">
            <h6>Wypożyczenie</h6>
            <?php if (!empty($weapon['current_holder_id'])): ?>
                <div class="alert alert-info py-2 mb-2">
                    Aktualnie w posiadaniu zawodnika ID <?= (int)$weapon['current_holder_id'] ?>
                </div>
                <form method="POST" action="<?= url('shooting/weapons/' . (int)$weapon['id'] . '/return') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-warning w-100"><i class="bi bi-arrow-return-left"></i> Zwrot broni</button>
                </form>
            <?php else: ?>
                <form method="POST" action="<?= url('shooting/weapons/' . (int)$weapon['id'] . '/assign') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label small">Zawodnik</label>
                        <select name="member_id" class="form-select form-select-sm" required>
                            <option value="">—</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Cel</label>
                        <input type="text" name="purpose" class="form-control form-control-sm" placeholder="np. trening/zawody">
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-right"></i> Wypożycz</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card p-3">
            <h6 class="mb-3">Historia wypożyczeń</h6>
            <?php if (empty($history)): ?>
                <div class="text-muted small">Brak historii.</div>
            <?php else: ?>
                <table class="table table-sm">
                    <thead><tr><th>Zawodnik</th><th>Wydano</th><th>Zwrot</th><th>Cel</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= View::e($h['last_name']) ?> <?= View::e($h['first_name']) ?></td>
                            <td><small><?= format_datetime($h['issued_at']) ?></small></td>
                            <td><small><?= $h['returned_at'] ? format_datetime($h['returned_at']) : '<span class="badge bg-warning">aktywne</span>' ?></small></td>
                            <td><small><?= View::e($h['purpose'] ?? '') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
