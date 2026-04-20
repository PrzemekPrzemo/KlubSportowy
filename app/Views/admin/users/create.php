<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Nowy super admin</h4>
    <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Powrót</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('admin/users/store') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label">Nazwa użytkownika <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_.\-]{3,40}">
                <small class="text-muted">3–40 znaków: litery, cyfry, <code>_</code> <code>.</code> <code>-</code></small>
            </div>
            <div class="col-md-6">
                <label class="form-label">E-mail <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pełna nazwa <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Telefon</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hasło <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" minlength="12" required autocomplete="new-password">
                <small class="text-muted">Minimum 12 znaków.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Powtórz hasło <span class="text-danger">*</span></label>
                <input type="password" name="password_confirm" class="form-control" minlength="12" required autocomplete="new-password">
            </div>
            <div class="col-12">
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Super admin ma pełny dostęp do wszystkich klubów i danych w systemie. Twórz konta tylko dla zaufanych operatorów.
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">Anuluj</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Utwórz konto</button>
            </div>
        </form>
    </div>
</div>
