<?php
use App\Helpers\View;
$isEdit = !empty($member);
$action = $isEdit ? url('members/' . (int)$member['id'] . '/update') : url('members/store');
$assignedIds = [];
if (!empty($sports)) {
    foreach ($sports as $s) { $assignedIds[] = (int)$s['club_sport_id']; }
}
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label"><?= __('form.member_number') ?> *</label>
            <input type="text" name="member_number" value="<?= View::e($member['member_number'] ?? $nextNumber ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.first_name') ?> *</label>
            <input type="text" name="first_name" value="<?= View::e($member['first_name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.last_name') ?> *</label>
            <input type="text" name="last_name" value="<?= View::e($member['last_name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.status') ?></label>
            <select name="status" class="form-select">
                <?php foreach (['aktywny','zawieszony','wykreslony','urlop'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($member['status'] ?? 'aktywny') === $s ? 'selected' : '' ?>><?= __('status.' . $s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.pesel') ?></label>
            <input type="text" name="pesel" value="<?= View::e($member['pesel'] ?? '') ?>" class="form-control" maxlength="11">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.birth_date') ?></label>
            <input type="date" name="birth_date" value="<?= View::e($member['birth_date'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.gender') ?></label>
            <select name="gender" class="form-select">
                <option value="">—</option>
                <option value="M" <?= ($member['gender'] ?? '') === 'M' ? 'selected' : '' ?>><?= __('form.gender_m') ?></option>
                <option value="K" <?= ($member['gender'] ?? '') === 'K' ? 'selected' : '' ?>><?= __('form.gender_f') ?></option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.join_date') ?> *</label>
            <input type="date" name="join_date" value="<?= View::e($member['join_date'] ?? date('Y-m-d')) ?>" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.email') ?></label>
            <input type="email" name="email" value="<?= View::e($member['email'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.phone') ?></label>
            <input type="text" name="phone" value="<?= View::e($member['phone'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.address_street') ?></label>
            <input type="text" name="address_street" value="<?= View::e($member['address_street'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.city') ?></label>
            <input type="text" name="address_city" value="<?= View::e($member['address_city'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label"><?= __('form.address_postal') ?></label>
            <input type="text" name="address_postal" value="<?= View::e($member['address_postal'] ?? '') ?>" class="form-control">
        </div>

        <div class="col-12">
            <label class="form-label"><?= __('form.sports_sections') ?></label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (($clubSports ?? []) as $cs): ?>
                    <label class="form-check">
                        <input type="checkbox" name="club_sport_ids[]"
                               value="<?= (int)$cs['club_sport_id'] ?>"
                               class="form-check-input"
                               <?= in_array((int)$cs['club_sport_id'], $assignedIds, true) ? 'checked' : '' ?>>
                        <span class="form-check-label">
                            <i class="bi <?= View::e($cs['icon']) ?>" style="color: <?= View::e($cs['color']) ?>"></i>
                            <?= View::e($cs['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label"><?= __('form.notes') ?></label>
            <textarea name="notes" class="form-control" rows="3"><?= View::e($member['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> <?= __('btn.save') ?></button>
        <a href="<?= url('members') ?>" class="btn btn-outline-secondary"><?= __('btn.cancel') ?></a>
    </div>
</form>
