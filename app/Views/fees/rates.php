<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h5 class="mb-3">Aktywne stawki</h5>
            <?php if (empty($rates)): ?>
                <div class="text-muted">Brak zdefiniowanych stawek.</div>
            <?php else: ?>
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Nazwa</th><th>Sport</th><th>Typ</th><th>Okres</th><th class="text-end">Kwota</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rates as $r): ?>
                        <tr>
                            <td><strong><?= View::e($r['name']) ?></strong></td>
                            <td><?= View::e($r['sport_name'] ?? '—') ?></td>
                            <td><?= View::e($r['fee_type']) ?></td>
                            <td><?= View::e($r['period']) ?></td>
                            <td class="text-end"><?= format_money($r['amount']) ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?= url('fees/rates/' . (int)$r['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Usunąć?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card p-3">
            <h5 class="mb-3">Nowa stawka</h5>
            <form method="POST" action="<?= url('fees/rates/store') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label">Nazwa *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Sport</label>
                    <select name="sport_id" class="form-select">
                        <option value="">— wszystkie —</option>
                        <?php foreach ($sports as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= View::e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Typ</label>
                    <select name="fee_type" class="form-select">
                        <?php foreach (['skladka','wpisowe','licencja','zawody','obóz','inne'] as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Okres</label>
                    <select name="period" class="form-select">
                        <option value="monthly">miesięcznie</option>
                        <option value="quarterly">kwartalnie</option>
                        <option value="yearly">rocznie</option>
                        <option value="one_time">jednorazowo</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kwota (zł)</label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-plus"></i> Dodaj</button>
            </form>
        </div>
    </div>
</div>
