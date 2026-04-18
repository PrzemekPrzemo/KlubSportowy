<?php
use App\Helpers\View;
$isEdit = !empty($exam);
$action = $isEdit ? url('medical/' . (int)$exam['id'] . '/update') : url('medical/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label"><?= __('form.member') ?> *</label>
            <select name="member_id" class="form-select" required>
                <option value=""><?= __('form.select_placeholder') ?></option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"
                            <?= ((int)($preselectMember ?? 0) === (int)$m['id']) ? 'selected' : '' ?>>
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                        (#<?= View::e($m['member_number']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.exam_type') ?></label>
            <input type="text" name="exam_type" value="<?= View::e($exam['exam_type'] ?? __('medical.exam_type_default')) ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.exam_date') ?> *</label>
            <input type="date" name="exam_date" value="<?= View::e($exam['exam_date'] ?? date('Y-m-d')) ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.valid_until') ?> *</label>
            <input type="date" name="valid_until" value="<?= View::e($exam['valid_until'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.doctor') ?></label>
            <input type="text" name="doctor_name" value="<?= View::e($exam['doctor_name'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label"><?= __('form.notes') ?></label>
            <textarea name="notes" class="form-control" rows="3"><?= View::e($exam['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> <?= __('btn.save') ?></button>
        <a href="<?= url('medical') ?>" class="btn btn-outline-secondary"><?= __('btn.cancel') ?></a>
        <?php if ($isEdit): ?>
            <form method="POST" action="<?= url('medical/' . (int)$exam['id'] . '/delete') ?>" onsubmit="return confirm('<?= __('common.confirm_delete') ?>')" class="ms-auto m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> <?= __('btn.delete') ?></button>
            </form>
        <?php endif; ?>
    </div>
</form>
