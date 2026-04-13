<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('support/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Temat *</label>
            <input type="text" name="subject" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Priorytet</label>
            <select name="priority" class="form-select">
                <option value="normal">normalny</option>
                <option value="low">niski</option>
                <option value="high">wysoki</option>
                <option value="urgent">pilny</option>
            </select></div>
        <div class="col-md-2"><label class="form-label">Kategoria</label>
            <select name="category" class="form-select">
                <option value="technical">techniczny</option>
                <option value="billing">billing</option>
                <option value="feature">pomysł</option>
                <option value="bug">błąd</option>
                <option value="other">inny</option>
            </select></div>
        <div class="col-12"><label class="form-label">Opis *</label>
            <textarea name="body" class="form-control" rows="6" required></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-send"></i> WYŚLIJ ZGŁOSZENIE</button>
        <a href="<?= url('support') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
