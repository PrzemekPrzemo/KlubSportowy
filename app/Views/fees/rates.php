<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-cash-stack text-primary me-2"></i>Stawki opłat</h3>
    <div class="btn-group">
        <a href="<?= url('fees') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-receipt"></i> Wpłaty
        </a>
        <a href="<?= url('fees/rates') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-cash-stack"></i> Stawki
        </a>
        <a href="<?= url('fees/discounts') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-percent"></i> Zniżki
        </a>
        <a href="<?= url('fees/assignments') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-people-fill"></i> Subskrypcje
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h5 class="mb-3"><?= __('fee.active_rates') ?></h5>
            <?php if (empty($rates)): ?>
                <div class="text-muted"><?= __('fee.no_rates') ?></div>
            <?php else: ?>
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th><?= __('fee.col_name') ?></th><th><?= __('fee.col_sport') ?></th><th><?= __('fee.col_type') ?></th><th><?= __('fee.col_period') ?></th><th class="text-end"><?= __('fee.col_amount') ?></th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rates as $r): ?>
                        <tr class="<?= empty($r['is_active']) ? 'text-muted' : '' ?>">
                            <td>
                                <strong><?= View::e($r['name']) ?></strong>
                                <?php if (empty($r['is_active'])): ?>
                                    <span class="badge bg-secondary ms-1" title="Nieaktywna — ukryta przy wprowadzaniu nowych opłat">nieaktywna</span>
                                <?php endif; ?>
                                <?php if (!empty($r['class_name'])): ?>
                                    <span class="badge bg-light text-secondary border ms-1"><?= View::e($r['class_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= View::e($r['sport_name'] ?? '—') ?></td>
                            <td><?= View::e($r['fee_type']) ?></td>
                            <td><?= View::e($r['period']) ?></td>
                            <td class="text-end"><?= format_money($r['amount']) ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url('fees/rates/' . (int)$r['id'] . '/edit') ?>"
                                       class="btn btn-sm btn-outline-primary" title="Edytuj">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="<?= url('fees/rates/' . (int)$r['id'] . '/toggle') ?>"
                                          class="d-inline" title="<?= !empty($r['is_active']) ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-<?= !empty($r['is_active']) ? 'warning' : 'success' ?>">
                                            <i class="bi bi-<?= !empty($r['is_active']) ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= url('fees/rates/' . (int)$r['id'] . '/delete') ?>"
                                          onsubmit="return confirm('<?= __('common.confirm_delete') ?>')" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Usuń">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
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
            <h5 class="mb-3"><?= __('fee.new_rate') ?></h5>
            <form method="POST" action="<?= url('fees/rates/store') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label"><?= __('form.name') ?> *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label"><?= __('form.sport') ?></label>
                    <select name="sport_id" class="form-select">
                        <option value=""><?= __('form.all_sports') ?></option>
                        <?php foreach ($sports as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= View::e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label"><?= __('fee.col_type') ?></label>
                    <select name="fee_type" class="form-select">
                        <?php foreach (['skladka' => 'fee.type_skladka', 'wpisowe' => 'fee.type_wpisowe', 'licencja' => 'fee.type_licencja', 'zawody' => 'fee.type_zawody', 'obóz' => 'fee.type_oboz', 'inne' => 'fee.type_inne'] as $val => $key): ?>
                            <option value="<?= $val ?>"><?= __($key) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label"><?= __('fee.col_period') ?></label>
                    <select name="period" class="form-select">
                        <option value="monthly"><?= __('fee.period_monthly') ?></option>
                        <option value="quarterly"><?= __('fee.period_quarterly') ?></option>
                        <option value="yearly"><?= __('fee.period_yearly') ?></option>
                        <option value="one_time"><?= __('fee.period_one_time') ?></option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= __('form.amount') ?> (zł)</label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-plus"></i> <?= __('btn.add') ?></button>
            </form>
        </div>
    </div>
</div>
