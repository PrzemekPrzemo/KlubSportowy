<?php
use App\Helpers\View;
$isEdit = !empty($announcement);
$action = $isEdit ? url('announcements/' . (int)$announcement['id'] . '/update') : url('announcements/store');
$pubFrom = !empty($announcement['publish_from']) ? str_replace(' ', 'T', substr($announcement['publish_from'],0,16)) : '';
$pubTo   = !empty($announcement['publish_to'])   ? str_replace(' ', 'T', substr($announcement['publish_to'],0,16))   : '';
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label"><?= __('form.title') ?> *</label>
            <input type="text" name="title" value="<?= View::e($announcement['title'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label"><?= __('form.priority') ?></label>
            <select name="priority" class="form-select">
                <?php foreach (['normal','important','urgent'] as $p): ?>
                    <option value="<?= $p ?>" <?= ($announcement['priority'] ?? 'normal') === $p ? 'selected' : '' ?>><?= __('ann.priority_' . $p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label"><?= __('form.target') ?></label>
            <select name="target" class="form-select">
                <?php foreach (['members','staff','all','public'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($announcement['target'] ?? 'members') === $t ? 'selected' : '' ?>><?= __('ann.target_' . $t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.sport') ?></label>
            <select name="sport_id" class="form-select">
                <option value=""><?= __('form.club_wide') ?></option>
                <?php foreach ($sports as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (int)($announcement['sport_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= View::e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.publish_from') ?></label>
            <input type="datetime-local" name="publish_from" value="<?= View::e($pubFrom) ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.publish_to') ?></label>
            <input type="datetime-local" name="publish_to" value="<?= View::e($pubTo) ?>" class="form-control">
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="published" value="1" id="pub" class="form-check-input" <?= ($announcement['published'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="pub"><?= __('form.published') ?></label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label"><?= __('form.content') ?> *</label>
            <textarea name="content" class="form-control" rows="8" required><?= View::e($announcement['content'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> <?= __('btn.save') ?></button>
        <a href="<?= url('announcements') ?>" class="btn btn-outline-secondary"><?= __('btn.cancel') ?></a>
    </div>
</form>
