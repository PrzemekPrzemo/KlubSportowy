<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('events/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label"><?= __('form.name') ?> *</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.type') ?></label>
            <select name="type" class="form-select">
                <option value="zawody"><?= __('event.type_zawody') ?></option>
                <option value="mecz"><?= __('event.type_mecz') ?></option>
                <option value="trening"><?= __('event.type_trening') ?></option>
                <option value="turniej"><?= __('event.type_turniej') ?></option>
                <option value="obóz"><?= __('event.type_oboz') ?></option>
                <option value="inny"><?= __('event.type_inny') ?></option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.sport') ?></label>
            <select name="sport_id" class="form-select">
                <option value=""><?= __('form.club_wide') ?></option>
                <?php foreach ($sports as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= View::e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.start_date') ?> *</label>
            <input type="datetime-local" name="event_date" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.end_date') ?></label>
            <input type="datetime-local" name="end_date" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label"><?= __('form.location') ?></label>
            <input type="text" name="location" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label"><?= __('form.description') ?></label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> <?= __('btn.save') ?></button>
        <a href="<?= url('events') ?>" class="btn btn-outline-secondary"><?= __('btn.cancel') ?></a>
    </div>
</form>
