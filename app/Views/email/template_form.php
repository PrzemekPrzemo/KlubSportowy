<?php use App\Helpers\View; ?>
<div class="alert alert-info small">
    <strong>Dostępne placeholdery:</strong>
    <code>{first_name}</code>, <code>{last_name}</code>, <code>{member_number}</code>,
    <code>{club_name}</code>, <code>{amount}</code>, <code>{license_type}</code>,
    <code>{license_number}</code>, <code>{valid_until}</code>, <code>{days}</code>,
    <code>{event_name}</code>, <code>{event_date}</code>, <code>{event_location}</code>,
    <code>{reset_link}</code>
</div>
<form method="POST" action="<?= url('email/templates/' . $template_type . '/save') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Typ szablonu</label>
            <input type="text" value="<?= View::e($template_type) ?>" class="form-control" readonly>
        </div>
        <div class="col-md-12">
            <label class="form-label">Nazwa</label>
            <input type="text" name="name" value="<?= View::e($template['name'] ?? $template_type) ?>" class="form-control">
        </div>
        <div class="col-md-12">
            <label class="form-label">Temat *</label>
            <input type="text" name="subject" value="<?= View::e($template['subject'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-12">
            <label class="form-label">Treść *</label>
            <textarea name="body" rows="12" class="form-control" required><?= View::e($template['body'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz (nadpisze per-klub)</button>
        <a href="<?= url('email/templates') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
